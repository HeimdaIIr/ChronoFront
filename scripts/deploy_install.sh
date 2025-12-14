#!/bin/bash
# Script d'installation - À EXÉCUTER SUR LE RASPBERRY PI
# Usage: sudo bash deploy_install.sh chronofront-v2_TIMESTAMP.tar.gz

set -e

if [ "$EUID" -ne 0 ]; then
    echo "❌ Ce script doit être exécuté en tant que root (sudo)"
    exit 1
fi

if [ -z "$1" ]; then
    echo "❌ Usage: sudo bash deploy_install.sh <archive.tar.gz>"
    exit 1
fi

ARCHIVE_FILE="$1"
APP_DIR="/var/www/chronofront-v2"
BACKUP_DIR="/var/www/chronofront-backup-$(date +%Y%m%d_%H%M%S)"
WEB_USER="www-data"

echo "🚀 Installation de ChronoFront V2 sur Raspberry Pi..."

# Vérifier que l'archive existe
if [ ! -f "$ARCHIVE_FILE" ]; then
    echo "❌ Fichier non trouvé : $ARCHIVE_FILE"
    exit 1
fi

# Sauvegarder l'ancienne version si elle existe
if [ -d "$APP_DIR" ]; then
    echo "💾 Sauvegarde de l'ancienne version..."
    mv "$APP_DIR" "$BACKUP_DIR"
    echo "✅ Ancienne version sauvegardée dans : $BACKUP_DIR"
fi

# Créer le dossier de l'application
echo "📁 Création du dossier de l'application..."
mkdir -p "$APP_DIR"

# Extraire l'archive
echo "📦 Extraction de l'archive..."
tar -xzf "$ARCHIVE_FILE" -C "$APP_DIR"

# Se déplacer dans le dossier de l'application
cd "$APP_DIR"

# Vérifier les prérequis système
echo "🔍 Vérification des prérequis..."

# Vérifier PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé"
    echo "Installation de PHP..."
    apt-get update
    apt-get install -y php8.1 php8.1-cli php8.1-fpm php8.1-sqlite3 php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip
fi

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✅ PHP version : $PHP_VERSION"

# Vérifier Composer
if ! command -v composer &> /dev/null; then
    echo "📥 Installation de Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
echo "✅ Composer installé"

# Vérifier Node.js et npm
if ! command -v node &> /dev/null; then
    echo "📥 Installation de Node.js..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt-get install -y nodejs
fi
echo "✅ Node.js version : $(node -v)"
echo "✅ npm version : $(npm -v)"

# Installer les dépendances PHP
echo "📥 Installation des dépendances PHP..."
composer install --no-dev --optimize-autoloader --no-interaction

# Installer les dépendances NPM et build
echo "📥 Installation des dépendances NPM..."
npm install

echo "🏗️ Build des assets frontend..."
npm run build

# Créer la base de données SQLite
echo "🗄️ Création de la base de données..."
touch database/database.sqlite
chmod 664 database/database.sqlite

# Générer la clé d'application
echo "🔑 Génération de la clé d'application..."
php artisan key:generate --force

# Exécuter les migrations
echo "🗃️ Exécution des migrations..."
php artisan migrate --force

# Configurer les permissions
echo "🔐 Configuration des permissions..."
chown -R $WEB_USER:$WEB_USER "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"
chmod 664 "$APP_DIR/database/database.sqlite"

# Optimiser l'application
echo "⚡ Optimisation de l'application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Créer le lien symbolique storage
php artisan storage:link

# Configuration Apache
echo "🌐 Configuration du serveur web..."

# Vérifier si Apache est installé
if command -v apache2 &> /dev/null; then
    echo "Configuration d'Apache..."

    # Créer le fichier de configuration Apache
    cat > /etc/apache2/sites-available/chronofront-v2.conf << 'APACHECONF'
<VirtualHost *:80>
    ServerName 107.course.ats-sport.com
    ServerAlias 107.course
    DocumentRoot /var/www/chronofront-v2/public

    <Directory /var/www/chronofront-v2/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/chronofront-error.log
    CustomLog ${APACHE_LOG_DIR}/chronofront-access.log combined
</VirtualHost>
APACHECONF

    # Activer les modules nécessaires
    a2enmod rewrite

    # Désactiver l'ancien site si nécessaire
    if [ -f /etc/apache2/sites-enabled/chronofront.conf ]; then
        a2dissite chronofront.conf
    fi

    # Activer le nouveau site
    a2ensite chronofront-v2.conf

    # Redémarrer Apache
    systemctl restart apache2

    echo "✅ Apache configuré et redémarré"
fi

# Afficher les informations finales
echo ""
echo "✅ ==============================================="
echo "✅ Installation terminée avec succès !"
echo "✅ ==============================================="
echo ""
echo "📍 Emplacement : $APP_DIR"
echo "🌐 URL : http://107.course.ats-sport.com"
echo "🌐 URL locale : http://192.168.10.157 (si connecté en RJ45)"
echo ""
echo "🧪 TESTS À EFFECTUER :"
echo ""
echo "1. Vérifier l'accès web :"
echo "   curl -I http://107.course.ats-sport.com"
echo ""
echo "2. Tester l'API de santé :"
echo "   curl http://107.course.ats-sport.com/api/health"
echo ""
echo "3. Tester la réception de détection RFID :"
echo "   curl -X PUT http://107.course.ats-sport.com/api/raspberry \\"
echo "     -H \"Serial: 120\" \\"
echo "     -H \"Content-Type: application/json\" \\"
echo "     -d '{\"tag\":\"TEST123\",\"time\":\"$(date -Iseconds)\"}'"
echo ""
echo "4. Vérifier les logs :"
echo "   tail -f $APP_DIR/storage/logs/laravel.log"
echo ""
echo "💾 Sauvegarde de l'ancienne version : $BACKUP_DIR"
echo ""
echo "🔧 En cas de problème, pour restaurer l'ancienne version :"
echo "   sudo rm -rf $APP_DIR"
echo "   sudo mv $BACKUP_DIR $APP_DIR"
echo "   sudo systemctl restart apache2"
echo ""
