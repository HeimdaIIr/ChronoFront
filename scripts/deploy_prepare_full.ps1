# Script de preparation COMPLET - VERSION WINDOWS POWERSHELL
# Inclut toutes les dependances pre-compilees (vendor + node_modules + build)
# Usage: .\scripts\deploy_prepare_full.ps1

$ErrorActionPreference = "Stop"

Write-Host "Preparation du deploiement COMPLET ChronoFront V2..." -ForegroundColor Green
Write-Host "Cette version inclut toutes les dependances pour eviter les telechargements sur la Pi" -ForegroundColor Yellow
Write-Host ""

# Configuration
$APP_NAME = "chronofront-v2"
$TIMESTAMP = Get-Date -Format "yyyyMMdd_HHmmss"
$ARCHIVE_NAME = "${APP_NAME}_full_${TIMESTAMP}.tar.gz"

Write-Host "Etape 1/5 : Installation des dependances PHP..." -ForegroundColor Cyan
composer install --no-dev --optimize-autoloader --no-interaction

Write-Host ""
Write-Host "Etape 2/5 : Installation des dependances NPM..." -ForegroundColor Cyan
npm install

Write-Host ""
Write-Host "Etape 3/5 : Compilation des assets..." -ForegroundColor Cyan
npm run build

Write-Host ""
Write-Host "Etape 4/5 : Creation de l'archive..." -ForegroundColor Cyan

# Creer le fichier .env pour la production
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

VITE_APP_NAME=ChronoFront
"@

# Sauvegarder .env actuel si existe
if (Test-Path ".env")
{
    Copy-Item ".env" ".env.backup" -Force
}

Set-Content -Path ".env" -Value $envContent

# Liste des fichiers/dossiers a inclure
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
if (Test-Path "storage/logs")
{
    Get-ChildItem "storage/logs/*.log" -ErrorAction SilentlyContinue | Remove-Item -Force
}

# Verifier si tar est disponible (Windows 10 1803+)
$hasTar = Get-Command tar -ErrorAction SilentlyContinue
$has7z = Get-Command 7z -ErrorAction SilentlyContinue

if ($hasTar)
{
    # Creer l'archive avec tar natif
    $tarArgs = @('-czf', $ARCHIVE_NAME) + $includes
    & tar $tarArgs
    Write-Host "Archive creee avec tar natif Windows" -ForegroundColor Green
}
elseif ($has7z)
{
    # Creer l'archive avec 7-Zip
    $tempTar = "temp_${TIMESTAMP}.tar"

    # Creer tar
    $tarArgs = @('a', '-ttar', $tempTar) + $includes
    & 7z $tarArgs

    # Compresser en gzip
    & 7z a -tgzip $ARCHIVE_NAME $tempTar

    # Nettoyer
    Remove-Item $tempTar -Force

    Write-Host "Archive creee avec 7-Zip" -ForegroundColor Green
}
else
{
    Write-Host "Erreur: Ni tar ni 7-Zip trouves" -ForegroundColor Red
    Write-Host "Installez 7-Zip depuis https://www.7-zip.org/" -ForegroundColor Yellow
    exit 1
}

# Restaurer .env original si existait
if (Test-Path ".env.backup")
{
    Move-Item ".env.backup" ".env" -Force
}

Write-Host ""
Write-Host "===============================================" -ForegroundColor Green
Write-Host "Archive COMPLETE creee : $ARCHIVE_NAME" -ForegroundColor Green
$sizeInMB = [math]::Round((Get-Item $ARCHIVE_NAME).Length / 1MB, 2)
Write-Host "Taille : $sizeInMB MB" -ForegroundColor Green
Write-Host "===============================================" -ForegroundColor Green
Write-Host ""
Write-Host "PROCHAINES ETAPES :" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Transferer via FTP vers /home/pi/ :" -ForegroundColor White
Write-Host "   - $ARCHIVE_NAME" -ForegroundColor Yellow
Write-Host "   - scripts/deploy_install_full.sh" -ForegroundColor Yellow
Write-Host ""
Write-Host "2. Se connecter en SSH :" -ForegroundColor White
Write-Host "   ssh pi@192.168.10.157" -ForegroundColor Yellow
Write-Host ""
Write-Host "3. Installer :" -ForegroundColor White
Write-Host "   chmod +x deploy_install_full.sh" -ForegroundColor Yellow
Write-Host "   sudo bash deploy_install_full.sh chronofront-v2_full_*.tar.gz" -ForegroundColor Yellow
Write-Host ""
