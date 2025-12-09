# Script de telechargement des paquets PHP 8.2 pour Raspberry Pi (ARM)
# A executer sur Windows

$ErrorActionPreference = "Stop"

Write-Host "Telechargement des paquets PHP 8.2 pour Raspberry Pi..." -ForegroundColor Green

# Creer le dossier de destination
$downloadDir = "php82-packages"
New-Item -ItemType Directory -Path $downloadDir -Force | Out-Null

# Base URL du depot Sury pour Debian Bullseye ARM
$baseUrl = "https://packages.sury.org/php/pool/main/p/php8.2"

# Liste des paquets a telecharger (versions approximatives - a ajuster)
$packages = @(
    "php8.2-common_8.2.29-5+0~20250907.81+debian11~1.gbp84dd39_armhf.deb",
    "php8.2-cli_8.2.29-5+0~20250907.81+debian11~1.gbp84dd39_armhf.deb",
    "php8.2-fpm_8.2.29-5+0~20250907.81+debian11~1.gbp84dd39_armhf.deb",
    "php8.2-sqlite3_8.2.29-5+0~20250907.81+debian11~1.gbp84dd39_armhf.deb",
    "php8.2-mbstring_8.2.29-5+0~20250907.81+debian11~1.gbp84dd39_armhf.deb",
    "php8.2-xml_8.2.29-5+0~20250907.81+debian11~1.gbp84dd39_armhf.deb",
    "php8.2-curl_8.2.29-5+0~20250907.81+debian11~1.gbp84dd39_armhf.deb",
    "php8.2-zip_8.2.29-5+0~20250907.81+debian11~1.gbp84dd39_armhf.deb"
)

# Telecharger chaque paquet
foreach ($package in $packages) {
    $url = "$baseUrl/$package"
    $output = Join-Path $downloadDir $package

    Write-Host "Telechargement de $package..." -ForegroundColor Cyan

    try {
        Invoke-WebRequest -Uri $url -OutFile $output
        Write-Host "  OK" -ForegroundColor Green
    } catch {
        Write-Host "  ERREUR: $_" -ForegroundColor Red
        Write-Host "  URL: $url" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Telechargement termine !" -ForegroundColor Green
Write-Host ""
Write-Host "Prochaines etapes :" -ForegroundColor Cyan
Write-Host "1. Transferer le dossier $downloadDir vers la Pi via FTP (dans /home/pi/)" -ForegroundColor White
Write-Host "2. Sur la Pi, executer :" -ForegroundColor White
Write-Host "   cd /home/pi/php82-packages" -ForegroundColor Yellow
Write-Host "   sudo dpkg -i *.deb" -ForegroundColor Yellow
Write-Host "   sudo apt-get install -f" -ForegroundColor Yellow
Write-Host ""
