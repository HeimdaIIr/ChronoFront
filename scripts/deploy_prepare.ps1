# Script de préparation du déploiement - VERSION WINDOWS POWERSHELL
# Usage: .\scripts\deploy_prepare.ps1

$ErrorActionPreference = "Stop"

Write-Host "🚀 Préparation du déploiement ChronoFront V2..." -ForegroundColor Green

# Configuration
$APP_NAME = "chronofront-v2"
$TIMESTAMP = Get-Date -Format "yyyyMMdd_HHmmss"
$DEPLOY_DIR = "deploy_$TIMESTAMP"
$ARCHIVE_NAME = "${APP_NAME}_${TIMESTAMP}.tar.gz"

# Créer le dossier de déploiement temporaire
Write-Host "📁 Création du dossier temporaire..." -ForegroundColor Cyan
New-Item -ItemType Directory -Path $DEPLOY_DIR -Force | Out-Null

# Liste des dossiers/fichiers à exclure
$excludes = @(
    "node_modules",
    "vendor",
    ".git",
    "storage\logs\*.log",
    "storage\framework\cache\*",
    "storage\framework\sessions\*",
    "storage\framework\views\*",
    "deploy_*",
    "*.tar.gz",
    "database\database.sqlite"
)

# Copier les fichiers (en excluant certains dossiers)
Write-Host "📋 Copie des fichiers de l'application..." -ForegroundColor Cyan

# Copier tous les fichiers sauf les exclusions
Get-ChildItem -Path . -Recurse -Exclude $excludes | ForEach-Object {
    if ($_.FullName -notmatch "node_modules|vendor|\.git|deploy_") {
        $targetPath = $_.FullName.Replace($PWD.Path, $DEPLOY_DIR)
        $targetDir = Split-Path -Parent $targetPath

        if (-not (Test-Path $targetDir)) {
            New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
        }

        if (-not $_.PSIsContainer) {
            Copy-Item $_.FullName -Destination $targetPath -Force
        }
    }
}

# Créer le fichier .env pour la production
Write-Host "⚙️ Création du fichier .env de production..." -ForegroundColor Cyan
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

Set-Content -Path "$DEPLOY_DIR\.env" -Value $envContent

# Créer les dossiers storage nécessaires
Write-Host "📂 Création de la structure des dossiers..." -ForegroundColor Cyan
$directories = @(
    "$DEPLOY_DIR\storage\app\public",
    "$DEPLOY_DIR\storage\framework\cache\data",
    "$DEPLOY_DIR\storage\framework\sessions",
    "$DEPLOY_DIR\storage\framework\views",
    "$DEPLOY_DIR\storage\logs",
    "$DEPLOY_DIR\bootstrap\cache"
)

foreach ($dir in $directories) {
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
}

# Vérifier si tar est disponible (Windows 10 1803+)
Write-Host "📦 Création de l'archive..." -ForegroundColor Cyan

if (Get-Command tar -ErrorAction SilentlyContinue) {
    # Utiliser tar natif de Windows 10
    tar -czf $ARCHIVE_NAME -C $DEPLOY_DIR .
    Write-Host "✅ Archive créée avec tar natif Windows" -ForegroundColor Green
}
elseif (Get-Command 7z -ErrorAction SilentlyContinue) {
    # Utiliser 7-Zip si disponible
    7z a -ttar temp.tar "$DEPLOY_DIR\*"
    7z a -tgzip $ARCHIVE_NAME temp.tar
    Remove-Item temp.tar -Force
    Write-Host "✅ Archive créée avec 7-Zip" -ForegroundColor Green
}
else {
    Write-Host "⚠️ Ni tar ni 7-Zip n'ont été trouvés." -ForegroundColor Yellow
    Write-Host "L'archive n'a pas pu être créée automatiquement." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "SOLUTION MANUELLE :" -ForegroundColor Yellow
    Write-Host "1. Installer 7-Zip depuis https://www.7-zip.org/" -ForegroundColor White
    Write-Host "2. Compresser le dossier '$DEPLOY_DIR' en .tar.gz avec 7-Zip" -ForegroundColor White
    Write-Host "3. Renommer l'archive en '$ARCHIVE_NAME'" -ForegroundColor White
    exit
}

# Nettoyer le dossier temporaire
Remove-Item -Recurse -Force $DEPLOY_DIR

# Afficher les instructions
Write-Host ""
Write-Host "✅ Archive créée : $ARCHIVE_NAME" -ForegroundColor Green
Write-Host ""
Write-Host "📤 PROCHAINES ÉTAPES :" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Transférer l'archive sur le Raspberry Pi 107 via FTP :" -ForegroundColor White
Write-Host "   - Fichier : $ARCHIVE_NAME" -ForegroundColor Yellow
Write-Host "   - Destination : /home/pi/" -ForegroundColor Yellow
Write-Host "   - Hôte FTP : 10.8.0.107 (ou 192.168.10.157 si RJ45)" -ForegroundColor Yellow
Write-Host ""
Write-Host "2. Transférer aussi le script d'installation :" -ForegroundColor White
Write-Host "   - Fichier : scripts/deploy_install.sh" -ForegroundColor Yellow
Write-Host "   - Destination : /home/pi/" -ForegroundColor Yellow
Write-Host ""
Write-Host "3. Se connecter en SSH avec PuTTY ou PowerShell :" -ForegroundColor White
Write-Host "   ssh pi@10.8.0.107" -ForegroundColor Yellow
Write-Host ""
Write-Host "4. Exécuter le script d'installation :" -ForegroundColor White
Write-Host "   chmod +x deploy_install.sh" -ForegroundColor Yellow
Write-Host "   sudo bash deploy_install.sh $ARCHIVE_NAME" -ForegroundColor Yellow
Write-Host ""
