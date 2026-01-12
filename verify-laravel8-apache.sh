#!/bin/bash

# Script de vérification pour ChronoFront Laravel 8 sur Apache
# Vérifie que l'installation s'est bien déroulée

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

ERRORS=0

log_check() {
    echo -e "${BLUE}[CHECK]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
    ((ERRORS++))
}

log_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1"
}

echo ""
echo "=========================================="
echo "ChronoFront Laravel 8 - Vérification"
echo "=========================================="
echo ""

# 1. Informations système
log_check "1. Informations système"
echo "  OS: $(cat /etc/os-release | grep PRETTY_NAME | cut -d'"' -f2)"
echo "  Hostname: $(hostname)"
echo "  IP lan: $(ip addr show lan 2>/dev/null | grep "inet " | awk '{print $2}' | cut -d/ -f1 || echo 'N/A')"
echo ""

# 2. PHP 7.3
log_check "2. Vérification PHP 7.3"
if php -v | grep -q "PHP 7.3"; then
    log_success "PHP 7.3 installé: $(php -v | head -n 1)"
else
    log_error "PHP 7.3 non trouvé"
fi

# Modules PHP requis
REQUIRED_MODULES=("sqlite3" "mbstring" "xml" "curl" "zip" "json" "bcmath" "tokenizer")
for module in "${REQUIRED_MODULES[@]}"; do
    if php -m | grep -q "$module"; then
        log_success "Module PHP $module: OK"
    else
        log_error "Module PHP $module: MANQUANT"
    fi
done
echo ""

# 3. Apache
log_check "3. Vérification Apache"
if systemctl is-active --quiet apache2; then
    log_success "Apache2 actif"
else
    log_error "Apache2 inactif"
fi

# Modules Apache
if apache2ctl -M 2>/dev/null | grep -q "rewrite_module"; then
    log_success "Module Apache rewrite: activé"
else
    log_error "Module Apache rewrite: non activé"
fi

if apache2ctl -M 2>/dev/null | grep -q "php7"; then
    log_success "Module Apache PHP: activé"
else
    log_error "Module Apache PHP: non activé"
fi
echo ""

# 4. ChronoFront installé
log_check "4. Vérification ChronoFront"
if [ -d "/var/www/chronofront" ]; then
    log_success "Répertoire /var/www/chronofront: OK"

    if [ -f "/var/www/chronofront/artisan" ]; then
        log_success "Fichier artisan trouvé (Laravel)"
    else
        log_error "Fichier artisan non trouvé"
    fi

    if [ -d "/var/www/chronofront/vendor" ]; then
        log_success "Dépendances Composer installées"
    else
        log_error "Dossier vendor manquant"
    fi
else
    log_error "Répertoire /var/www/chronofront non trouvé"
fi
echo ""

# 5. Permissions
log_check "5. Vérification des permissions"
if [ -w "/var/www/chronofront/storage" ]; then
    log_success "storage/ accessible en écriture"
else
    log_error "storage/ non accessible en écriture"
fi

if [ -w "/var/www/chronofront/bootstrap/cache" ]; then
    log_success "bootstrap/cache/ accessible en écriture"
else
    log_error "bootstrap/cache/ non accessible en écriture"
fi
echo ""

# 6. Base de données SQLite
log_check "6. Vérification base de données"
if [ -f "/var/www/chronofront/database/database.sqlite" ]; then
    log_success "Base de données SQLite créée"

    if [ -r "/var/www/chronofront/database/database.sqlite" ]; then
        log_success "Base de données accessible en lecture"
    else
        log_error "Base de données non accessible en lecture"
    fi

    # Vérifier les tables
    TABLE_COUNT=$(sqlite3 /var/www/chronofront/database/database.sqlite "SELECT COUNT(*) FROM sqlite_master WHERE type='table';" 2>/dev/null || echo "0")
    if [ "$TABLE_COUNT" -gt "0" ]; then
        log_success "Tables créées ($TABLE_COUNT tables)"
    else
        log_warning "Aucune table trouvée (migrations non exécutées?)"
    fi
else
    log_error "Base de données SQLite non trouvée"
fi
echo ""

# 7. Configuration Apache
log_check "7. Vérification VirtualHost Apache"
if [ -f "/etc/apache2/sites-available/000-course.conf" ]; then
    log_success "Fichier 000-course.conf trouvé"

    if grep -q "/var/www/chronofront/public" /etc/apache2/sites-available/000-course.conf; then
        log_success "DocumentRoot pointe vers Laravel"
    else
        log_warning "DocumentRoot ne pointe pas vers /var/www/chronofront/public"
    fi

    if grep -q "*.course.ats-sport.com" /etc/apache2/sites-available/000-course.conf; then
        log_success "ServerAlias *.course.ats-sport.com configuré"
    else
        log_error "ServerAlias wildcard manquant"
    fi
else
    log_error "Fichier 000-course.conf non trouvé"
fi

# VirtualHost actif
if [ -L "/etc/apache2/sites-enabled/000-course.conf" ]; then
    log_success "VirtualHost activé"
else
    log_error "VirtualHost non activé"
fi
echo ""

# 8. Composer
log_check "8. Vérification Composer"
if command -v composer &> /dev/null; then
    log_success "Composer installé: $(composer --version 2>/dev/null | head -n 1)"
else
    log_error "Composer non trouvé"
fi
echo ""

# 9. Fichier .env Laravel
log_check "9. Vérification configuration Laravel"
if [ -f "/var/www/chronofront/.env" ]; then
    log_success "Fichier .env présent"

    if grep -q "DB_CONNECTION=sqlite" /var/www/chronofront/.env; then
        log_success "Configuration SQLite: OK"
    else
        log_warning "DB_CONNECTION n'est pas sqlite"
    fi

    if grep -q "APP_KEY=base64:" /var/www/chronofront/.env; then
        log_success "APP_KEY généré"
    else
        log_error "APP_KEY non généré"
    fi
else
    log_error "Fichier .env non trouvé"
fi
echo ""

# 10. Test HTTP
log_check "10. Test de connectivité HTTP"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost 2>/dev/null || echo "000")
if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "302" ]; then
    log_success "HTTP localhost: OK (code $HTTP_CODE)"
else
    log_error "HTTP localhost: ERREUR (code $HTTP_CODE)"
fi

# Test endpoint API
API_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/raspberry 2>/dev/null || echo "000")
if [ "$API_CODE" == "200" ] || [ "$API_CODE" == "405" ] || [ "$API_CODE" == "422" ]; then
    log_success "Endpoint /api/raspberry: OK (code $API_CODE)"
else
    log_warning "Endpoint /api/raspberry: code $API_CODE"
fi
echo ""

# 11. Logs Apache récents
log_check "11. Logs Apache récents (erreurs)"
if [ -f "/var/log/apache2/error.log" ]; then
    ERROR_COUNT=$(tail -50 /var/log/apache2/error.log 2>/dev/null | grep -i "error\|critical\|fatal" | wc -l)
    if [ "$ERROR_COUNT" -gt "0" ]; then
        log_warning "$ERROR_COUNT erreurs dans les 50 dernières lignes"
        echo ""
        echo "Dernières erreurs:"
        tail -50 /var/log/apache2/error.log | grep -i "error\|critical\|fatal" | tail -5
    else
        log_success "Pas d'erreurs récentes dans les logs"
    fi
else
    log_warning "Fichier de log non trouvé"
fi
echo ""

# 12. Routes Laravel
log_check "12. Vérification routes Laravel"
if [ -f "/var/www/chronofront/artisan" ]; then
    cd /var/www/chronofront
    if sudo -u www-data php artisan route:list 2>/dev/null | grep -q "api/raspberry"; then
        log_success "Route /api/raspberry enregistrée"
    else
        log_warning "Route /api/raspberry non trouvée"
    fi
else
    log_error "Impossible de vérifier les routes"
fi
echo ""

# Résumé
echo "=========================================="
if [ $ERRORS -eq 0 ]; then
    log_success "=== Vérification réussie ! ==="
    log_success "Aucune erreur détectée"
    echo ""
    log_info "Application accessible via:"
    LAN_IP=$(ip addr show lan 2>/dev/null | grep "inet " | awk '{print $2}' | cut -d/ -f1 || echo "N/A")
    echo "  - http://localhost"
    echo "  - http://${LAN_IP}"
    echo "  - http://XXX.course.ats-sport.com"
    exit 0
else
    log_error "=== $ERRORS erreur(s) détectée(s) ==="
    log_warning "Consultez les messages ci-dessus pour plus de détails"
    exit 1
fi
echo "=========================================="
