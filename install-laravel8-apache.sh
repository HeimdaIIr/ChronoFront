#!/bin/bash

# Installation ChronoFront Laravel 8 sur Raspberry Pi avec Apache
# Adapté au système existant (Apache + ServerAlias)

set -e

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# Vérifier si root
if [[ $EUID -ne 0 ]]; then
   log_error "Ce script doit être exécuté en tant que root (sudo)"
   exit 1
fi

echo ""
echo "=========================================="
echo "  ChronoFront Laravel 8 - Installation"
echo "  Raspberry Pi Buster - Apache"
echo "=========================================="
echo ""

# Demander le numéro de lecteur
read -p "Numéro du lecteur RFID (ex: 107): " READER_NUMBER

if [[ ! "$READER_NUMBER" =~ ^[0-9]+$ ]]; then
    log_error "Le numéro de lecteur doit être un nombre"
    exit 1
fi

DOMAIN="${READER_NUMBER}.course.ats-sport.com"
SHORT_DOMAIN="${READER_NUMBER}.course"
INSTALL_DIR="/var/www/chronofront"
DB_PATH="${INSTALL_DIR}/database/database.sqlite"
OLD_BACKUP="/home/dev/web.backup.$(date +%Y%m%d_%H%M%S)"

log_info "Configuration:"
log_info "  - Reader Number: ${READER_NUMBER}"
log_info "  - Full Domain: ${DOMAIN}"
log_info "  - Short Domain: ${SHORT_DOMAIN}"
log_info "  - Install Directory: ${INSTALL_DIR}"
log_info "  - Backup ancien système: ${OLD_BACKUP}"
echo ""

read -p "Continuer l'installation ? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    log_warning "Installation annulée"
    exit 1
fi

# Step 1: Backup de l'ancien système
log_info "Step 1: Backup de l'ancien système..."
if [ -d "/home/dev/web" ]; then
    cp -r /home/dev/web "$OLD_BACKUP"
    log_success "Backup créé: $OLD_BACKUP"
else
    log_warning "Ancien système non trouvé dans /home/dev/web"
fi

# Step 2: Mise à jour du système
log_info "Step 2: Mise à jour du système..."
apt-get update -qq
log_success "Système mis à jour"

# Step 3: Installation PHP 7.3 et modules
log_info "Step 3: Installation de PHP 7.3 et modules..."
apt-get install -y -qq \
    php7.3 \
    php7.3-cli \
    php7.3-common \
    php7.3-sqlite3 \
    php7.3-mbstring \
    php7.3-xml \
    php7.3-curl \
    php7.3-zip \
    php7.3-json \
    php7.3-bcmath \
    php7.3-tokenizer \
    libapache2-mod-php7.3 \
    sqlite3 \
    git \
    unzip

log_success "PHP 7.3 et modules installés"

# Step 4: Vérifier Apache
log_info "Step 4: Vérification Apache..."
if ! systemctl is-active --quiet apache2; then
    log_error "Apache2 n'est pas actif"
    exit 1
fi
log_success "Apache2 actif"

# Step 5: Activer modules Apache nécessaires
log_info "Step 5: Activation modules Apache..."
a2enmod rewrite
a2enmod php7.3
systemctl restart apache2
log_success "Modules Apache activés"

# Step 6: Installation de Composer
log_info "Step 6: Installation de Composer..."
if [ ! -f /usr/local/bin/composer ]; then
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        log_error "Checksum Composer invalide"
        rm composer-setup.php
        exit 1
    fi

    php composer-setup.php --quiet
    rm composer-setup.php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    log_success "Composer installé"
else
    log_success "Composer déjà installé"
fi

# Step 7: Cloner le repository ChronoFront
log_info "Step 7: Clonage ChronoFront Laravel 8..."
if [ -d "$INSTALL_DIR" ]; then
    log_warning "Le répertoire $INSTALL_DIR existe déjà, suppression..."
    rm -rf "$INSTALL_DIR"
fi

# Note: Remplacer par votre vrai repository Git
git clone -b laravel8-php73 https://github.com/HeimdaIIr/ChronoFront.git "$INSTALL_DIR" --quiet
log_success "ChronoFront cloné"

# Step 8: Installation des dépendances Composer
log_info "Step 8: Installation des dépendances..."
cd "$INSTALL_DIR"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --quiet
log_success "Dépendances installées"

# Step 9: Configuration Laravel
log_info "Step 9: Configuration Laravel..."

# Créer le fichier .env s'il n'existe pas
if [ ! -f "$INSTALL_DIR/.env" ]; then
    cp "$INSTALL_DIR/.env.example" "$INSTALL_DIR/.env"
fi

# Générer la clé d'application
php artisan key:generate --force

# Configuration SQLite dans .env
sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=sqlite/" "$INSTALL_DIR/.env"
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_PATH}|" "$INSTALL_DIR/.env"

log_success "Laravel configuré"

# Step 10: Créer la base de données SQLite
log_info "Step 10: Création de la base de données..."
mkdir -p "$INSTALL_DIR/database"
touch "$DB_PATH"
chmod 664 "$DB_PATH"

# Exécuter les migrations
php artisan migrate --force
log_success "Base de données créée"

# Step 11: Permissions
log_info "Step 11: Configuration des permissions..."
chown -R www-data:www-data "$INSTALL_DIR"
chmod -R 755 "$INSTALL_DIR"
chmod -R 775 "$INSTALL_DIR/storage"
chmod -R 775 "$INSTALL_DIR/bootstrap/cache"
chmod 664 "$DB_PATH"
log_success "Permissions configurées"

# Step 12: Configuration VirtualHost Apache
log_info "Step 12: Configuration VirtualHost Apache..."

# Backup de l'ancienne config
cp /etc/apache2/sites-available/000-course.conf /etc/apache2/sites-available/000-course.conf.backup

# Créer la nouvelle configuration
cat > /etc/apache2/sites-available/000-course.conf << EOF
<VirtualHost *:80>
    ServerName ${SHORT_DOMAIN}
    ServerAlias *.course
    ServerAlias *.course.ats-sport.com

    ServerAdmin webmaster@localhost
    DocumentRoot ${INSTALL_DIR}/public

    <Directory ${INSTALL_DIR}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Laravel logs
    ErrorLog \${APACHE_LOG_DIR}/chronofront-error.log
    CustomLog \${APACHE_LOG_DIR}/chronofront-access.log combined
</VirtualHost>
EOF

log_success "VirtualHost configuré"

# Step 13: Mettre à jour /etc/hosts
log_info "Step 13: Mise à jour /etc/hosts..."

# Récupérer l'IP de l'interface bridge lan
LAN_IP=$(ip addr show lan 2>/dev/null | grep "inet " | awk '{print $2}' | cut -d/ -f1 || echo "192.168.10.157")

# Vérifier si les entrées existent déjà
if ! grep -q "${READER_NUMBER}.course" /etc/hosts; then
    cat >> /etc/hosts << EOF

# ChronoFront Laravel 8
${LAN_IP}     ${READER_NUMBER}.course
${LAN_IP}     www.${READER_NUMBER}.course
EOF
    log_success "/etc/hosts mis à jour"
else
    log_success "/etc/hosts déjà configuré"
fi

# Step 14: Redémarrer Apache
log_info "Step 14: Redémarrage Apache..."
systemctl restart apache2
systemctl enable apache2
log_success "Apache redémarré"

# Step 15: Optimisation Laravel
log_info "Step 15: Optimisation Laravel..."
cd "$INSTALL_DIR"
php artisan config:cache
php artisan route:cache
php artisan view:cache
log_success "Laravel optimisé"

# Step 16: Test de l'installation
log_info "Step 16: Test de l'installation..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost)
if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "302" ]; then
    log_success "Application accessible (HTTP $HTTP_CODE)"
else
    log_warning "Code HTTP inattendu: $HTTP_CODE"
fi

echo ""
echo "=========================================="
log_success "=== Installation terminée ! ==="
echo "=========================================="
echo ""
log_info "ChronoFront Laravel 8 accessible via:"
log_info "  - http://${DOMAIN}"
log_info "  - http://${SHORT_DOMAIN}"
log_info "  - http://localhost"
log_info "  - http://${LAN_IP}"
echo ""
log_info "Endpoint RFID:"
log_info "  - POST http://${DOMAIN}/api/raspberry"
echo ""
log_info "Interface RFID Live:"
log_info "  - http://${DOMAIN}/rfidlive"
echo ""
log_warning "Ancien système sauvegardé dans:"
log_warning "  ${OLD_BACKUP}"
echo ""
log_info "Pour restaurer l'ancien système:"
log_info "  sudo cp /etc/apache2/sites-available/000-course.conf.backup /etc/apache2/sites-available/000-course.conf"
log_info "  sudo systemctl restart apache2"
echo ""
