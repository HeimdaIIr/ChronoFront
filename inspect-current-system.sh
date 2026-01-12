#!/bin/bash

# Script de diagnostic pour inspecter le système ChronoFront actuel
# À exécuter via SSH sur le Raspberry Pi

echo "=========================================="
echo "ChronoFront - Diagnostic Système Actuel"
echo "=========================================="
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 1. Informations système de base
echo -e "${BLUE}[1] Informations système${NC}"
echo "---"
echo "Hostname: $(hostname)"
echo "OS: $(cat /etc/os-release | grep PRETTY_NAME | cut -d'"' -f2)"
echo "Kernel: $(uname -r)"
echo "IP addresses:"
ip addr show | grep "inet " | awk '{print "  - " $NF ": " $2}'
echo ""

# 2. Services actifs
echo -e "${BLUE}[2] Services web et DNS${NC}"
echo "---"
systemctl is-active nginx >/dev/null 2>&1 && echo -e "Nginx: ${GREEN}✓ actif${NC}" || echo -e "Nginx: ${RED}✗ inactif${NC}"
systemctl is-active apache2 >/dev/null 2>&1 && echo -e "Apache: ${GREEN}✓ actif${NC}" || echo -e "Apache: ${RED}✗ inactif${NC}"
systemctl is-active dnsmasq >/dev/null 2>&1 && echo -e "dnsmasq: ${GREEN}✓ actif${NC}" || echo -e "dnsmasq: ${RED}✗ inactif${NC}"
systemctl is-active hostapd >/dev/null 2>&1 && echo -e "hostapd: ${GREEN}✓ actif${NC}" || echo -e "hostapd: ${RED}✗ inactif${NC}"
systemctl is-active php7.3-fpm >/dev/null 2>&1 && echo -e "PHP-FPM 7.3: ${GREEN}✓ actif${NC}" || echo -e "PHP-FPM 7.3: ${RED}✗ inactif${NC}"
echo ""

# 3. Configuration Nginx
echo -e "${BLUE}[3] Configuration Nginx${NC}"
echo "---"
if [ -f /etc/nginx/sites-enabled/default ]; then
    echo "Fichier: /etc/nginx/sites-enabled/default"
    echo ""
    cat /etc/nginx/sites-enabled/default
else
    echo -e "${YELLOW}Fichier default non trouvé${NC}"
    echo "Fichiers dans sites-enabled:"
    ls -la /etc/nginx/sites-enabled/ 2>/dev/null || echo "Répertoire non accessible"
fi
echo ""

# 4. Configuration dnsmasq
echo -e "${BLUE}[4] Configuration DNS (dnsmasq)${NC}"
echo "---"
if [ -f /etc/dnsmasq.conf ]; then
    echo "Configuration dnsmasq:"
    cat /etc/dnsmasq.conf | grep -v "^#" | grep -v "^$"
else
    echo -e "${RED}dnsmasq.conf non trouvé${NC}"
fi
echo ""

# 5. Configuration hostapd (WiFi AP)
echo -e "${BLUE}[5] Configuration WiFi Access Point${NC}"
echo "---"
if [ -f /etc/hostapd/hostapd.conf ]; then
    echo "Configuration hostapd:"
    cat /etc/hostapd/hostapd.conf | grep -v "^#" | grep -v "^$"
else
    echo -e "${YELLOW}hostapd.conf non trouvé${NC}"
fi
echo ""

# 6. Interfaces réseau
echo -e "${BLUE}[6] Configuration réseau${NC}"
echo "---"
if [ -f /etc/dhcpcd.conf ]; then
    echo "Configuration dhcpcd:"
    cat /etc/dhcpcd.conf | grep -v "^#" | grep -v "^$" | tail -20
fi
echo ""

# 7. Structure de l'application
echo -e "${BLUE}[7] Application ChronoFront${NC}"
echo "---"
echo "Recherche de l'application dans les emplacements courants..."
POSSIBLE_PATHS=(
    "/var/www/html"
    "/var/www/chronofront"
    "/home/pi/chronofront"
    "/opt/chronofront"
    "/usr/share/nginx/html"
)

for path in "${POSSIBLE_PATHS[@]}"; do
    if [ -d "$path" ]; then
        echo -e "${GREEN}Trouvé: $path${NC}"
        echo "Contenu:"
        ls -la "$path" 2>/dev/null | head -15
        echo ""

        # Vérifier si c'est une app Laravel
        if [ -f "$path/artisan" ]; then
            echo -e "${GREEN}Application Laravel détectée!${NC}"
            echo "Version Laravel:"
            grep "laravel/framework" "$path/composer.json" 2>/dev/null || echo "composer.json non trouvé"
        fi
        echo ""
    fi
done

# 8. Version PHP
echo -e "${BLUE}[8] Configuration PHP${NC}"
echo "---"
php -v 2>/dev/null || echo -e "${RED}PHP CLI non trouvé${NC}"
echo ""
echo "Modules PHP installés:"
php -m 2>/dev/null | head -20 || echo "Impossible de lister les modules"
echo ""

# 9. Test de résolution DNS
echo -e "${BLUE}[9] Test résolution DNS${NC}"
echo "---"
echo "Test de résolution locale:"
nslookup 107.course.ats-sport.com 127.0.0.1 2>/dev/null || echo "nslookup non disponible"
echo ""
nslookup 107.course 127.0.0.1 2>/dev/null || echo "Format court non résolu"
echo ""

# 10. Fichier /etc/hosts
echo -e "${BLUE}[10] Fichier /etc/hosts${NC}"
echo "---"
cat /etc/hosts
echo ""

# 11. Ports en écoute
echo -e "${BLUE}[11] Ports en écoute${NC}"
echo "---"
echo "Port 80 (HTTP):"
netstat -tlnp 2>/dev/null | grep ":80 " || ss -tlnp 2>/dev/null | grep ":80 "
echo ""
echo "Port 53 (DNS):"
netstat -ulnp 2>/dev/null | grep ":53 " || ss -ulnp 2>/dev/null | grep ":53 "
echo ""

# 12. Logs récents
echo -e "${BLUE}[12] Logs récents (20 dernières lignes)${NC}"
echo "---"
echo "Nginx error log:"
tail -20 /var/log/nginx/error.log 2>/dev/null || echo "Log non accessible"
echo ""

echo "=========================================="
echo "Diagnostic terminé"
echo "=========================================="
