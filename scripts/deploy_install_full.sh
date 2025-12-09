#!/bin/bash
# Script d'installation COMPLET - À EXÉCUTER SUR LE RASPBERRY PI
# Cette version ne télécharge RIEN - tout est dans l'archive
# Usage: sudo bash deploy_install_full.sh chronofront-v2_full_TIMESTAMP.tar.gz

set -e

if [ "$EUID" -ne 0 ]; then
    echo "❌ Ce script doit être exécuté en tant que root (sudo)"
    exit 1
fi

if [ -z "$1" ]; then
    echo "❌ Usage: sudo bash deploy_install_full.sh <archive.tar.gz>"
    exit 1
fi

ARCHIVE_FILE="$1"
APP_DIR="/var/www/chronofront-v2"
BACKUP_DIR="/var/www/chronofront-backup-$(date +%Y%m%d_%H%M%S)"
WEB_USER="www-data"

echo "🚀 Installation COMPLÈTE de ChronoFront V2 sur Raspberry Pi..."
echo "Cette version n'a besoin d'AUCUN téléchargement (tout est pré-compilé)"
echo ""

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
echo "📦 Extraction de l'archive (cela peut prendre quelques minutes)..."
tar -xzf "$ARCHIVE_FILE" -C "$APP_DIR"

# Se déplacer dans le dossier de l'application
cd "$APP_DIR"

# Vérifier PHP
echo "🔍 Vérification de PHP..."
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé"
    exit 1
fi
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✅ PHP version : $PHP_VERSION"

# Vérifier que vendor/ existe
if [ ! -d "vendor" ]; then
    echo "❌ Erreur: Le dossier vendor/ n'existe pas dans l'archive"
    echo "Veuillez utiliser deploy_prepare_full.ps1 pour créer l'archive"
    exit 1
fi

# Vérifier que node_modules/ existe
if [ ! -d "node_modules" ]; then
    echo "⚠️ Avertissement: Le dossier node_modules/ n'existe pas"
    echo "Les assets frontend pourraient ne pas fonctionner"
fi

# Vérifier que public/build/ existe (assets compilés)
if [ ! -d "public/build" ]; then
    echo "⚠️ Avertissement: Les assets compilés (public/build/) n'existent pas"
    echo "L'interface pourrait ne pas s'afficher correctement"
fi

# Créer la base de données SQLite
echo "🗄️ Création de la base de données..."
mkdir -p database
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
chown $WEB_USER:$WEB_USER "$APP_DIR/database/database.sqlite"

# Optimiser l'application
echo "⚡ Optimisation de l'application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Créer le lien symbolique storage
if [ ! -L "public/storage" ]; then
    php artisan storage:link
fi

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
    ServerAlias 192.168.10.157
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
        a2dissite chronofront.conf 2>/dev/null || true
    fi
    if [ -f /etc/apache2/sites-enabled/000-default.conf ]; then
        a2dissite 000-default.conf 2>/dev/null || true
    fi

    # Activer le nouveau site
    a2ensite chronofront-v2.conf

    # Tester la configuration
    apache2ctl configtest

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
echo "🌐 URL VPN/4G : http://107.course.ats-sport.com"
echo "🌐 URL locale : http://192.168.10.157"
echo ""
echo "🧪 TESTS À EFFECTUER :"
echo ""
echo "1. Tester l'accès web (depuis votre PC) :"
echo "   http://192.168.10.157"
echo ""
echo "2. Tester l'API de santé :"
echo "   curl http://192.168.10.157/api/health"
echo ""
echo "3. Tester la réception de détection RFID :"
echo "   curl -X PUT http://192.168.10.157/api/raspberry \\"
echo "     -H 'Serial: 120' \\"
echo "     -H 'Content-Type: application/json' \\"
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
