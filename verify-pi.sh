#!/bin/bash

###############################################################################
# ChronoFront - Verification script for Raspberry Pi
# Run this to verify the installation is working correctly
###############################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

INSTALL_DIR="/var/www/chronofront"
ERRORS=0
WARNINGS=0

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║      ChronoFront - Installation Verification Tool         ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

check_pass() {
    echo -e "${GREEN}✓${NC} $1"
}

check_fail() {
    echo -e "${RED}✗${NC} $1"
    ((ERRORS++))
}

check_warn() {
    echo -e "${YELLOW}⚠${NC} $1"
    ((WARNINGS++))
}

check_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Check 1: System info
echo -e "${BLUE}[1/12]${NC} System Information"
check_info "OS: $(lsb_release -d | cut -f2)"
check_info "Kernel: $(uname -r)"
check_info "Hostname: $(hostname)"
check_info "IP Address: $(hostname -I | awk '{print $1}')"
echo ""

# Check 2: PHP
echo -e "${BLUE}[2/12]${NC} PHP Installation"
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r 'echo PHP_VERSION;')
    if [[ "$PHP_VERSION" =~ ^7\.3 ]]; then
        check_pass "PHP 7.3 installed (${PHP_VERSION})"
    else
        check_fail "Wrong PHP version (${PHP_VERSION}). Required: 7.3.x"
    fi
else
    check_fail "PHP not found"
fi

# Check required PHP extensions
for ext in sqlite3 mbstring xml curl zip json; do
    if php -m | grep -q "^${ext}$"; then
        check_pass "PHP extension: ${ext}"
    else
        check_fail "Missing PHP extension: ${ext}"
    fi
done
echo ""

# Check 3: Nginx
echo -e "${BLUE}[3/12]${NC} Nginx Web Server"
if systemctl is-active --quiet nginx; then
    check_pass "Nginx is running"
    if nginx -t 2>&1 | grep -q "syntax is ok"; then
        check_pass "Nginx configuration is valid"
    else
        check_fail "Nginx configuration has errors"
    fi
else
    check_fail "Nginx is not running"
fi
echo ""

# Check 4: PHP-FPM
echo -e "${BLUE}[4/12]${NC} PHP-FPM Service"
if systemctl is-active --quiet php7.3-fpm; then
    check_pass "PHP-FPM is running"
else
    check_fail "PHP-FPM is not running"
fi

# Check PHP-FPM socket
if [ -S /var/run/php/php7.3-fpm.sock ]; then
    check_pass "PHP-FPM socket exists"
else
    check_fail "PHP-FPM socket not found"
fi
echo ""

# Check 5: DNS (dnsmasq)
echo -e "${BLUE}[5/12]${NC} DNS Configuration"
if systemctl is-active --quiet dnsmasq; then
    check_pass "dnsmasq is running"
else
    check_warn "dnsmasq is not running (optional for .course domains)"
fi

if grep -q "address=/course/" /etc/dnsmasq.conf 2>/dev/null; then
    check_pass ".course domain configured in dnsmasq"
else
    check_warn ".course domain not configured (optional)"
fi
echo ""

# Check 6: ChronoFront installation
echo -e "${BLUE}[6/12]${NC} ChronoFront Installation"
if [ -d "${INSTALL_DIR}" ]; then
    check_pass "Installation directory exists"
else
    check_fail "Installation directory not found: ${INSTALL_DIR}"
fi

if [ -f "${INSTALL_DIR}/artisan" ]; then
    check_pass "Laravel artisan found"
else
    check_fail "Laravel artisan not found"
fi

if [ -f "${INSTALL_DIR}/.env" ]; then
    check_pass ".env file exists"
else
    check_fail ".env file not found"
fi
echo ""

# Check 7: File permissions
echo -e "${BLUE}[7/12]${NC} File Permissions"
if [ -d "${INSTALL_DIR}/storage" ]; then
    STORAGE_PERMS=$(stat -c "%a" "${INSTALL_DIR}/storage")
    if [ "$STORAGE_PERMS" = "775" ] || [ "$STORAGE_PERMS" = "777" ]; then
        check_pass "storage directory is writable (${STORAGE_PERMS})"
    else
        check_warn "storage directory permissions: ${STORAGE_PERMS} (should be 775)"
    fi
else
    check_fail "storage directory not found"
fi

STORAGE_OWNER=$(stat -c "%U" "${INSTALL_DIR}/storage" 2>/dev/null || echo "unknown")
if [ "$STORAGE_OWNER" = "www-data" ]; then
    check_pass "storage owned by www-data"
else
    check_warn "storage owned by: ${STORAGE_OWNER} (should be www-data)"
fi
echo ""

# Check 8: Database
echo -e "${BLUE}[8/12]${NC} Database"
if [ -f "${INSTALL_DIR}/database/database.sqlite" ]; then
    check_pass "SQLite database file exists"

    # Check if database is accessible
    if sqlite3 "${INSTALL_DIR}/database/database.sqlite" "SELECT 1;" &>/dev/null; then
        check_pass "Database is accessible"

        # Count tables
        TABLE_COUNT=$(sqlite3 "${INSTALL_DIR}/database/database.sqlite" "SELECT count(*) FROM sqlite_master WHERE type='table';" 2>/dev/null)
        if [ "$TABLE_COUNT" -gt 0 ]; then
            check_pass "Database has ${TABLE_COUNT} tables"
        else
            check_warn "Database has no tables (migrations not run?)"
        fi
    else
        check_fail "Database is not accessible"
    fi
else
    check_fail "Database file not found"
fi
echo ""

# Check 9: Nginx configuration
echo -e "${BLUE}[9/12]${NC} Nginx Site Configuration"
if [ -f /etc/nginx/sites-available/chronofront ]; then
    check_pass "chronofront nginx config exists"
else
    check_fail "chronofront nginx config not found"
fi

if [ -L /etc/nginx/sites-enabled/chronofront ]; then
    check_pass "chronofront site is enabled"
else
    check_fail "chronofront site is not enabled"
fi
echo ""

# Check 10: Composer dependencies
echo -e "${BLUE}[10/12]${NC} Composer Dependencies"
if [ -d "${INSTALL_DIR}/vendor" ]; then
    check_pass "vendor directory exists"

    VENDOR_SIZE=$(du -sh "${INSTALL_DIR}/vendor" 2>/dev/null | cut -f1)
    check_info "vendor directory size: ${VENDOR_SIZE}"
else
    check_fail "vendor directory not found (run composer install)"
fi
echo ""

# Check 11: Laravel configuration
echo -e "${BLUE}[11/12]${NC} Laravel Configuration"
if [ -f "${INSTALL_DIR}/bootstrap/cache/config.php" ]; then
    check_pass "Config cached"
else
    check_warn "Config not cached (run php artisan config:cache)"
fi

if [ -f "${INSTALL_DIR}/bootstrap/cache/routes-v7.php" ]; then
    check_pass "Routes cached"
else
    check_warn "Routes not cached (run php artisan route:cache)"
fi

# Check APP_KEY
if grep -q "APP_KEY=base64:" "${INSTALL_DIR}/.env" 2>/dev/null; then
    check_pass "APP_KEY is set"
else
    check_fail "APP_KEY is not set (run php artisan key:generate)"
fi
echo ""

# Check 12: Network connectivity test
echo -e "${BLUE}[12/12]${NC} HTTP Connectivity Test"
LOCAL_IP=$(hostname -I | awk '{print $1}')

# Test localhost
if curl -s -o /dev/null -w "%{http_code}" http://localhost 2>/dev/null | grep -q "200\|302"; then
    check_pass "localhost responds (HTTP)"
else
    check_warn "localhost not responding"
fi

# Test local IP
if curl -s -o /dev/null -w "%{http_code}" "http://${LOCAL_IP}" 2>/dev/null | grep -q "200\|302"; then
    check_pass "${LOCAL_IP} responds (HTTP)"
else
    check_warn "${LOCAL_IP} not responding"
fi

# Try to detect .course domain
if grep -q "address=/course/" /etc/dnsmasq.conf 2>/dev/null; then
    READER_NUM=$(grep "address=/course/" /etc/dnsmasq.conf | head -1 | grep -oP '\d+\.course' | grep -oP '^\d+' || echo "")
    if [ -n "$READER_NUM" ]; then
        check_info "Detected reader number: ${READER_NUM}"
        check_info "Domain should be: ${READER_NUM}.course"
    fi
fi
echo ""

# Summary
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                    Verification Summary                    ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ Perfect! All checks passed.${NC}"
    echo ""
    echo "ChronoFront is ready to use!"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠ ${WARNINGS} warning(s) found.${NC}"
    echo ""
    echo "ChronoFront should work, but some optimizations are recommended."
    exit 0
else
    echo -e "${RED}✗ ${ERRORS} error(s) and ${WARNINGS} warning(s) found.${NC}"
    echo ""
    echo "Please fix the errors above before using ChronoFront."
    exit 1
fi
