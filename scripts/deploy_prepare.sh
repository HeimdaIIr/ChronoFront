#!/bin/bash
# Script de préparation du déploiement - À EXÉCUTER SUR LE PC
# Crée une archive prête à déployer sur Raspberry Pi

set -e

echo "🚀 Préparation du déploiement ChronoFront V2..."

# Configuration
APP_NAME="chronofront-v2"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DEPLOY_DIR="deploy_${TIMESTAMP}"
ARCHIVE_NAME="${APP_NAME}_${TIMESTAMP}.tar.gz"

# Créer le dossier de déploiement temporaire
echo "📁 Création du dossier temporaire..."
mkdir -p "$DEPLOY_DIR"

# Copier les fichiers nécessaires (exclure node_modules, vendor, etc.)
echo "📋 Copie des fichiers de l'application..."
rsync -av --exclude='node_modules' \
          --exclude='vendor' \
          --exclude='.git' \
          --exclude='storage/logs/*.log' \
          --exclude='storage/framework/cache/*' \
          --exclude='storage/framework/sessions/*' \
          --exclude='storage/framework/views/*' \
          --exclude='deploy_*' \
          --exclude='*.tar.gz' \
          --exclude='database/database.sqlite' \
          ./ "$DEPLOY_DIR/"

# Créer le fichier .env pour la production
echo "⚙️ Création du fichier .env de production..."
cat > "$DEPLOY_DIR/.env" << 'EOF'
APP_NAME=ChronoFront
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://107.course.ats-sport.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/chronofront-v2/database/database.sqlite

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

VITE_APP_NAME="${APP_NAME}"
EOF

# Créer les dossiers storage nécessaires
echo "📂 Création de la structure des dossiers..."
mkdir -p "$DEPLOY_DIR/storage/app/public"
mkdir -p "$DEPLOY_DIR/storage/framework/cache/data"
mkdir -p "$DEPLOY_DIR/storage/framework/sessions"
mkdir -p "$DEPLOY_DIR/storage/framework/views"
mkdir -p "$DEPLOY_DIR/storage/logs"
mkdir -p "$DEPLOY_DIR/bootstrap/cache"

# Créer l'archive
echo "📦 Création de l'archive..."
tar -czf "$ARCHIVE_NAME" -C "$DEPLOY_DIR" .

# Nettoyer le dossier temporaire
rm -rf "$DEPLOY_DIR"

# Afficher les instructions
echo ""
echo "✅ Archive créée : $ARCHIVE_NAME"
echo ""
echo "📤 PROCHAINES ÉTAPES :"
echo ""
echo "1. Transférer l'archive sur le Raspberry Pi 107 :"
echo "   scp $ARCHIVE_NAME pi@10.8.0.107:/home/pi/"
echo ""
echo "2. Se connecter en SSH :"
echo "   ssh pi@10.8.0.107"
echo ""
echo "3. Exécuter le script d'installation :"
echo "   cd /home/pi"
echo "   sudo bash deploy_install.sh $ARCHIVE_NAME"
echo ""
echo "NOTE : Le script deploy_install.sh doit également être transféré :"
echo "   scp scripts/deploy_install.sh pi@10.8.0.107:/home/pi/"
echo ""
