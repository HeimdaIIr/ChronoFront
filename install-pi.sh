#!/bin/bash

###############################################################################
# ChronoFront - Installation script for Raspberry Pi (Buster - PHP 7.3)
# This script installs and configures ChronoFront on a Raspberry Pi
# with automatic XXX.course domain based on reader serial number
###############################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Log functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    log_error "Please run as root (use sudo)"
    exit 1
fi

log_info "=== ChronoFront Installation for Raspberry Pi Buster ==="
echo ""

# Step 1: Get reader serial number
log_info "Step 1: Configuration"
echo -n "Enter reader serial number (e.g., 107, 115, 120): "
read READER_NUMBER

if [[ ! "$READER_NUMBER" =~ ^[0-9]+$ ]]; then
    log_error "Invalid reader number. Must be numeric."
    exit 1
fi

DOMAIN="${READER_NUMBER}.course"
INSTALL_DIR="/var/www/chronofront"
DB_PATH="${INSTALL_DIR}/database/database.sqlite"

log_info "Configuration:"
log_info "  - Reader Number: ${READER_NUMBER}"
log_info "  - Domain: ${DOMAIN}"
log_info "  - Install Directory: ${INSTALL_DIR}"
echo ""

# Step 2: Update system
log_info "Step 2: Updating system packages..."
apt-get update -qq
apt-get upgrade -y -qq
log_success "System updated"

# Step 3: Install required packages
log_info "Step 3: Installing required packages..."
apt-get install -y -qq \
    nginx \
    php7.3-fpm \
    php7.3-cli \
    php7.3-sqlite3 \
    php7.3-mbstring \
    php7.3-xml \
    php7.3-curl \
    php7.3-zip \
    php7.3-json \
    sqlite3 \
    composer \
    git \
    dnsmasq \
    hostapd

log_success "Packages installed"

# Step 4: Verify PHP version
log_info "Step 4: Verifying PHP installation..."
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
log_info "PHP Version: ${PHP_VERSION}"

if [[ ! "$PHP_VERSION" =~ ^7\.3 ]]; then
    log_error "PHP 7.3 is required. Current version: ${PHP_VERSION}"
    exit 1
fi
log_success "PHP 7.3 verified"

# Step 5: Create install directory
log_info "Step 5: Creating installation directory..."
mkdir -p "${INSTALL_DIR}"
log_success "Directory created: ${INSTALL_DIR}"

# Step 6: Copy ChronoFront files
log_info "Step 6: Installing ChronoFront files..."
CURRENT_DIR=$(pwd)

# Check if we're in the ChronoFront directory
if [ ! -f "artisan" ]; then
    log_error "This script must be run from the ChronoFront root directory"
    exit 1
fi

# Copy all files except .git and node_modules
rsync -av --exclude='.git' --exclude='node_modules' --exclude='vendor' . "${INSTALL_DIR}/"
log_success "Files copied"

# Step 7: Install Composer dependencies
log_info "Step 7: Installing Composer dependencies..."
cd "${INSTALL_DIR}"
composer install --no-dev --optimize-autoloader -q
log_success "Composer dependencies installed"

# Step 8: Set permissions
log_info "Step 8: Setting permissions..."
chown -R www-data:www-data "${INSTALL_DIR}"
chmod -R 755 "${INSTALL_DIR}"
chmod -R 775 "${INSTALL_DIR}/storage"
chmod -R 775 "${INSTALL_DIR}/bootstrap/cache"
log_success "Permissions set"

# Step 9: Configure environment
log_info "Step 9: Configuring environment..."
if [ ! -f "${INSTALL_DIR}/.env" ]; then
    cp "${INSTALL_DIR}/.env.example" "${INSTALL_DIR}/.env"
fi

# Update .env for production
sed -i "s/APP_ENV=.*/APP_ENV=production/" "${INSTALL_DIR}/.env"
sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" "${INSTALL_DIR}/.env"
sed -i "s|APP_URL=.*|APP_URL=http://${DOMAIN}|" "${INSTALL_DIR}/.env"
sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=sqlite|" "${INSTALL_DIR}/.env"
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_PATH}|" "${INSTALL_DIR}/.env"

# Generate application key
php artisan key:generate --force
log_success "Environment configured"

# Step 10: Setup database
log_info "Step 10: Setting up database..."
mkdir -p "${INSTALL_DIR}/database"
touch "${DB_PATH}"
chown www-data:www-data "${DB_PATH}"
chmod 664 "${DB_PATH}"

php artisan migrate --force
log_success "Database initialized"

# Step 11: Configure Nginx
log_info "Step 11: Configuring Nginx..."
cat > /etc/nginx/sites-available/chronofront << EOF
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    # Accept any numeric.course domain
    server_name ~^(?<reader_num>\d+)\.course$ ${DOMAIN};

    root ${INSTALL_DIR}/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Max upload size for CSV imports
    client_max_body_size 50M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;

        # Increase timeout for long operations
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Disable access logs for performance (optional)
    # access_log off;
}
EOF

# Enable site
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/chronofront /etc/nginx/sites-enabled/

# Test Nginx config
nginx -t
systemctl restart nginx
systemctl enable nginx
log_success "Nginx configured"

# Step 12: Configure dnsmasq for .course domain
log_info "Step 12: Configuring DNS (dnsmasq)..."

# Backup original dnsmasq config
cp /etc/dnsmasq.conf /etc/dnsmasq.conf.backup

cat >> /etc/dnsmasq.conf << EOF

# ChronoFront - Resolve *.course to localhost
address=/course/127.0.0.1

# Also resolve to WiFi AP IP (if hostapd is configured)
address=/course/192.168.4.1
EOF

systemctl restart dnsmasq
systemctl enable dnsmasq
log_success "DNS configured"

# Step 13: Configure PHP-FPM
log_info "Step 13: Configuring PHP-FPM..."
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/7.3/fpm/php.ini
systemctl restart php7.3-fpm
systemctl enable php7.3-fpm
log_success "PHP-FPM configured"

# Step 14: Optimize Laravel
log_info "Step 14: Optimizing Laravel..."
cd "${INSTALL_DIR}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
log_success "Laravel optimized"

# Step 15: Create startup script and systemd service
log_info "Step 15: Creating startup script and systemd service..."

# Create startup script that fixes permissions on every boot
cat > "${INSTALL_DIR}/fix-permissions.sh" << 'SCRIPT'
#!/bin/bash
# ChronoFront - Fix database and storage permissions
# Runs automatically on boot via systemd

INSTALL_DIR="/var/www/chronofront"
DB_DIR="${INSTALL_DIR}/database"

# Fix database directory permissions
chown www-data:www-data "$DB_DIR"
chmod 775 "$DB_DIR"

# Fix ALL sqlite files (account_*.sqlite, ats_*.sqlite, database.sqlite, system.sqlite, etc.)
find "$DB_DIR" -maxdepth 1 -name "*.sqlite*" -exec chown www-data:www-data {} \;
find "$DB_DIR" -maxdepth 1 -name "*.sqlite*" -exec chmod 664 {} \;

# Fix storage and cache permissions
chown -R www-data:www-data "${INSTALL_DIR}/storage"
chmod -R 775 "${INSTALL_DIR}/storage"
chown -R www-data:www-data "${INSTALL_DIR}/bootstrap/cache"
chmod -R 775 "${INSTALL_DIR}/bootstrap/cache"
SCRIPT

chmod +x "${INSTALL_DIR}/fix-permissions.sh"

cat > /etc/systemd/system/chronofront.service << EOF
[Unit]
Description=ChronoFront - Fix permissions on boot
After=local-fs.target

[Service]
Type=oneshot
ExecStart=${INSTALL_DIR}/fix-permissions.sh
RemainAfterExit=yes

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable chronofront.service
log_success "Startup script and systemd service created"

# Step 16: Final checks
log_info "Step 16: Running final checks..."

# Check Nginx
if systemctl is-active --quiet nginx; then
    log_success "✓ Nginx is running"
else
    log_error "✗ Nginx is not running"
fi

# Check PHP-FPM
if systemctl is-active --quiet php7.3-fpm; then
    log_success "✓ PHP-FPM is running"
else
    log_error "✗ PHP-FPM is not running"
fi

# Check database
if [ -f "${DB_PATH}" ]; then
    log_success "✓ Database file exists"
else
    log_error "✗ Database file not found"
fi

# Check DNS
if systemctl is-active --quiet dnsmasq; then
    log_success "✓ dnsmasq is running"
else
    log_error "✗ dnsmasq is not running"
fi

echo ""
log_success "=== Installation completed successfully! ==="
echo ""
log_info "ChronoFront is now accessible at:"
log_info "  - http://${DOMAIN}"
log_info "  - http://localhost"
log_info "  - http://$(hostname -I | awk '{print $1}')"
echo ""
log_info "Next steps:"
log_info "  1. Test the installation: http://${DOMAIN}"
log_info "  2. Import your participants CSV"
log_info "  3. Configure your RFID readers"
echo ""
log_warning "IMPORTANT: For WiFi Access Point functionality,"
log_warning "you need to configure hostapd separately."
echo ""
