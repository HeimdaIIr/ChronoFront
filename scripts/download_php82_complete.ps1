# Script de telechargement COMPLET - PHP 8.2 + dependances systeme
# Telecharge TOUS les paquets necessaires pour installer PHP 8.2 offline

$ErrorActionPreference = "Stop"

Write-Host "Telechargement COMPLET des paquets pour PHP 8.2..." -ForegroundColor Green
Write-Host "Cela peut prendre 5-10 minutes" -ForegroundColor Yellow
Write-Host ""

# Creer le dossier de destination
$downloadDir = "php82-complete"
New-Item -ItemType Directory -Path $downloadDir -Force | Out-Null

# URLs de base
$suryBase = "https://packages.sury.org/php/pool/main"
$debianBase = "http://ftp.debian.org/debian/pool/main"

# Fonction de telechargement
function Download-Package {
    param($url, $filename)
    $output = Join-Path $downloadDir $filename
    Write-Host "  Telechargement de $filename..." -ForegroundColor Cyan
    try {
        Invoke-WebRequest -Uri $url -OutFile $output -TimeoutSec 300
        Write-Host "    OK" -ForegroundColor Green
        return $true
    } catch {
        Write-Host "    ERREUR: $_" -ForegroundColor Red
        return $false
    }
}

# 1. Paquets PHP 8.2 principaux
Write-Host "1/5 Paquets PHP 8.2 principaux..." -ForegroundColor Yellow
$phpVersion = "8.2.29-5+0~20250907.81+debian11~1.gbp84dd39"
$phpPackages = @(
    "p/php8.2/php8.2-common_${phpVersion}_armhf.deb",
    "p/php8.2/php8.2-cli_${phpVersion}_armhf.deb",
    "p/php8.2/php8.2-fpm_${phpVersion}_armhf.deb",
    "p/php8.2/php8.2-sqlite3_${phpVersion}_armhf.deb",
    "p/php8.2/php8.2-mbstring_${phpVersion}_armhf.deb",
    "p/php8.2/php8.2-xml_${phpVersion}_armhf.deb",
    "p/php8.2/php8.2-curl_${phpVersion}_armhf.deb",
    "p/php8.2/php8.2-zip_${phpVersion}_armhf.deb",
    "p/php8.2/php8.2-opcache_${phpVersion}_armhf.deb",
    "p/php8.2/php8.2-readline_${phpVersion}_armhf.deb"
)

foreach ($pkg in $phpPackages) {
    $url = "$suryBase/$pkg"
    $filename = Split-Path $pkg -Leaf
    Download-Package $url $filename
}

# 2. Dependances critiques systeme (libc6, libffi, etc.)
Write-Host ""
Write-Host "2/5 Dependances systeme critiques..." -ForegroundColor Yellow
$systemPackages = @(
    @{url="$debianBase/g/glibc/libc6_2.31-13+rpi1+deb11u11_armhf.deb"; name="libc6_2.31-13+rpi1+deb11u11_armhf.deb"},
    @{url="$debianBase/l/libffi/libffi7_3.3-6_armhf.deb"; name="libffi7_3.3-6_armhf.deb"},
    @{url="$debianBase/libo/libonig/libonig5_6.9.6-1.1_armhf.deb"; name="libonig5_6.9.6-1.1_armhf.deb"},
    @{url="$debianBase/g/glibc/libc-bin_2.31-13+rpi1+deb11u11_armhf.deb"; name="libc-bin_2.31-13+rpi1+deb11u11_armhf.deb"}
)

foreach ($pkg in $systemPackages) {
    Download-Package $pkg.url $pkg.name
}

# 3. Bibliotheques SSL/Crypto
Write-Host ""
Write-Host "3/5 Bibliotheques SSL et crypto..." -ForegroundColor Yellow
$sslPackages = @(
    @{url="$debianBase/o/openssl/libssl1.1_1.1.1w-0+deb11u1_armhf.deb"; name="libssl1.1_1.1.1w-0+deb11u1_armhf.deb"},
    @{url="$debianBase/libx/libxml2/libxml2_2.9.10+dfsg-6.7+deb11u4_armhf.deb"; name="libxml2_2.9.10+dfsg-6.7+deb11u4_armhf.deb"},
    @{url="$debianBase/c/curl/libcurl4_7.74.0-1.3+deb11u14_armhf.deb"; name="libcurl4_7.74.0-1.3+deb11u14_armhf.deb"}
)

foreach ($pkg in $sslPackages) {
    Download-Package $pkg.url $pkg.name
}

# 4. Dependances Apache PHP
Write-Host ""
Write-Host "4/5 Modules Apache pour PHP..." -ForegroundColor Yellow
$apachePackages = @(
    "l/libapache2-mod-php8.2/libapache2-mod-php8.2_${phpVersion}_armhf.deb"
)

foreach ($pkg in $apachePackages) {
    $url = "$suryBase/$pkg"
    $filename = Split-Path $pkg -Leaf
    Download-Package $url $filename
}

# 5. Dependances additionnelles
Write-Host ""
Write-Host "5/5 Dependances additionnelles..." -ForegroundColor Yellow
$additionalPackages = @(
    @{url="$debianBase/libs/libsodium/libsodium23_1.0.18-1_armhf.deb"; name="libsodium23_1.0.18-1_armhf.deb"},
    @{url="$debianBase/z/zlib/zlib1g_1.2.11.dfsg-2+deb11u2_armhf.deb"; name="zlib1g_1.2.11.dfsg-2+deb11u2_armhf.deb"},
    @{url="$debianBase/libz/libzip/libzip4_1.7.3-1+deb11u1_armhf.deb"; name="libzip4_1.7.3-1+deb11u1_armhf.deb"}
)

foreach ($pkg in $additionalPackages) {
    Download-Package $pkg.url $pkg.name
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Telechargement termine !" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Dossier cree : $downloadDir" -ForegroundColor Cyan
Write-Host ""
Write-Host "PROCHAINES ETAPES :" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Transferer le dossier '$downloadDir' vers la Pi (FTP dans /home/pi/)" -ForegroundColor White
Write-Host ""
Write-Host "2. Sur la Pi, installer dans cet ordre :" -ForegroundColor White
Write-Host "   cd /home/pi/$downloadDir" -ForegroundColor Cyan
Write-Host "   # Dependances systeme d'abord (ORDRE IMPORTANT)" -ForegroundColor Cyan
Write-Host "   sudo dpkg -i libc-bin_*.deb libc6_*.deb" -ForegroundColor Cyan
Write-Host "   sudo dpkg -i libffi7_*.deb libonig5_*.deb" -ForegroundColor Cyan
Write-Host "   sudo dpkg -i libssl1.1_*.deb libxml2_*.deb libcurl4_*.deb" -ForegroundColor Cyan
Write-Host "   sudo dpkg -i libsodium23_*.deb zlib1g_*.deb libzip4_*.deb" -ForegroundColor Cyan
Write-Host "   # Puis PHP 8.2" -ForegroundColor Cyan
Write-Host "   sudo dpkg -i php8.2-common_*.deb" -ForegroundColor Cyan
Write-Host "   sudo dpkg -i php8.2-*.deb" -ForegroundColor Cyan
Write-Host "   sudo dpkg -i libapache2-mod-php8.2_*.deb" -ForegroundColor Cyan
Write-Host "   # Resoudre les dependances restantes" -ForegroundColor Cyan
Write-Host "   sudo apt-get install -f" -ForegroundColor Cyan
Write-Host ""
Write-Host "3. Configurer Apache :" -ForegroundColor White
Write-Host "   sudo a2dismod php7.3" -ForegroundColor Cyan
Write-Host "   sudo a2enmod php8.2" -ForegroundColor Cyan
Write-Host "   sudo systemctl restart apache2" -ForegroundColor Cyan
Write-Host ""
Write-Host "4. Verifier : php -v" -ForegroundColor White
Write-Host ""
