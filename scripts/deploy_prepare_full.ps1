# Script de préparation COMPLET - VERSION WINDOWS POWERSHELL
# Inclut toutes les dépendances pré-compilées (vendor + node_modules + build)
# Usage: .\scripts\deploy_prepare_full.ps1

$ErrorActionPreference = "Stop"

Write-Host "🚀 Préparation du déploiement COMPLET ChronoFront V2..." -ForegroundColor Green
Write-Host "Cette version inclut toutes les dépendances pour éviter les téléchargements sur la Pi" -ForegroundColor Yellow
Write-Host ""

# Configuration
$APP_NAME = "chronofront-v2"
$TIMESTAMP = Get-Date -Format "yyyyMMdd_HHmmss"
$ARCHIVE_NAME = "${APP_NAME}_full_${TIMESTAMP}.tar.gz"

Write-Host "📦 Étape 1/5 : Installation des dépendances PHP..." -ForegroundColor Cyan
composer install --no-dev --optimize-autoloader --no-interaction

Write-Host ""
Write-Host "📦 Étape 2/5 : Installation des dépendances NPM..." -ForegroundColor Cyan
npm install

Write-Host ""
Write-Host "🏗️ Étape 3/5 : Compilation des assets..." -ForegroundColor Cyan
npm run build

Write-Host ""
Write-Host "📁 Étape 4/5 : Création de l'archive..." -ForegroundColor Cyan

# Créer le fichier .env pour la production
$envContent = @"
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

VITE_APP_NAME=`${APP_NAME}
"@

# Sauvegarder .env actuel si existe
if (Test-Path ".env") {
    Copy-Item ".env" ".env.backup" -Force
}

Set-Content -Path ".env" -Value $envContent

# Liste des fichiers/dossiers à inclure
$includes = @(
    "app",
    "bootstrap",
    "config",
    "database",
    "public",
    "resources",
    "routes",
    "storage",
    "vendor",
    "node_modules",
    "artisan",
    "composer.json",
    "composer.lock",
    "package.json",
    "package-lock.json",
    "vite.config.js",
    ".env"
)

# Nettoyer storage
if (Test-Path "storage/logs") {
    Get-ChildItem "storage/logs/*.log" -ErrorAction SilentlyContinue | Remove-Item -Force
}

# Vérifier si tar est disponible (Windows 10 1803+)
if (Get-Command tar -ErrorAction SilentlyContinue) {
    # Créer l'archive avec tar natif
    $includeArgs = $includes -join " "
    tar -czf $ARCHIVE_NAME $includes
    Write-Host "✅ Archive créée avec tar natif Windows" -ForegroundColor Green
}
elseif (Get-Command 7z -ErrorAction SilentlyContinue) {
    # Créer l'archive avec 7-Zip
    $tempTar = "temp_${TIMESTAMP}.tar"

    # Créer tar
    & 7z a -ttar $tempTar $includes

    # Compresser en gzip
    & 7z a -tgzip $ARCHIVE_NAME $tempTar

    # Nettoyer
    Remove-Item $tempTar -Force

    Write-Host "✅ Archive créée avec 7-Zip" -ForegroundColor Green
}
else {
    Write-Host "❌ Erreur: Ni tar ni 7-Zip trouvés" -ForegroundColor Red
    Write-Host "Installez 7-Zip depuis https://www.7-zip.org/" -ForegroundColor Yellow
    exit 1
}

# Restaurer .env original si existait
if (Test-Path ".env.backup") {
    Move-Item ".env.backup" ".env" -Force
}

Write-Host ""
Write-Host "✅ ===============================================" -ForegroundColor Green
Write-Host "✅ Archive COMPLÈTE créée : $ARCHIVE_NAME" -ForegroundColor Green
Write-Host "✅ Taille : $((Get-Item $ARCHIVE_NAME).Length / 1MB) MB" -ForegroundColor Green
Write-Host "✅ ===============================================" -ForegroundColor Green
Write-Host ""
Write-Host "📤 PROCHAINES ÉTAPES :" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Transférer via FTP vers /home/pi/ :" -ForegroundColor White
Write-Host "   - $ARCHIVE_NAME" -ForegroundColor Yellow
Write-Host "   - scripts/deploy_install_full.sh" -ForegroundColor Yellow
Write-Host ""
Write-Host "2. Se connecter en SSH :" -ForegroundColor White
Write-Host "   ssh pi@192.168.10.157" -ForegroundColor Yellow
Write-Host ""
Write-Host "3. Installer :" -ForegroundColor White
Write-Host "   chmod +x deploy_install_full.sh" -ForegroundColor Yellow
Write-Host "   sudo bash deploy_install_full.sh $ARCHIVE_NAME" -ForegroundColor Yellow
Write-Host ""
