#!/bin/bash

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

clear

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  🚀 ChronoFront RFID Server - Démarrage"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Détecter l'IP réseau
echo "📍 Détection de l'adresse IP réseau..."
IP=$(ipconfig getifaddr en0 2>/dev/null)

if [ -z "$IP" ]; then
    IP=$(ipconfig getifaddr en1 2>/dev/null)
fi

if [ -n "$IP" ]; then
    echo -e "${GREEN}✅ IP réseau détectée: $IP${NC}"
else
    echo -e "${YELLOW}⚠️  Impossible de détecter l'IP réseau${NC}"
    echo -e "${YELLOW}   Le serveur sera accessible en localhost seulement${NC}"
    IP="localhost"
fi

echo ""

# Vérifier si un serveur tourne déjà
echo "🔍 Vérification des serveurs existants..."
EXISTING_SERVER=$(lsof -i :8000 2>/dev/null | grep LISTEN)

if [ -n "$EXISTING_SERVER" ]; then
    echo -e "${YELLOW}⚠️  Un serveur tourne déjà sur le port 8000${NC}"
    echo "$EXISTING_SERVER"
    echo ""
    read -p "Voulez-vous l'arrêter et redémarrer ? (o/N) " -n 1 -r
    echo ""

    if [[ $REPLY =~ ^[Oo]$ ]]; then
        echo "Arrêt du serveur existant..."
        pkill -f "artisan serve"
        sleep 2
        echo -e "${GREEN}✅ Serveur arrêté${NC}"
    else
        echo "Serveur existant conservé. Sortie."
        exit 0
    fi
fi

echo ""

# Afficher les informations de connexion
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  📋 INFORMATIONS DE CONNEXION"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${BLUE}Interface de monitoring RFID Live Ultra:${NC}"
echo "  Local:  http://localhost:8000/rfidlive-ultra"

if [ "$IP" != "localhost" ]; then
    echo "  Réseau: http://$IP:8000/rfidlive-ultra"
    echo ""
    echo -e "${BLUE}Configuration du lecteur RFID portable (reader_id: 200):${NC}"
    echo "  URL:     http://$IP:8000/api/raspberry"
    echo "  Méthode: PUT"
    echo "  Port:    8000"
fi

echo ""
echo -e "${BLUE}Application principale:${NC}"
echo "  Local:  http://localhost:8000"

if [ "$IP" != "localhost" ]; then
    echo "  Réseau: http://$IP:8000"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Vérifier le pare-feu (macOS)
if [ "$IP" != "localhost" ]; then
    echo "🛡️  Vérification du pare-feu..."
    FW_STATUS=$(sudo /usr/libexec/ApplicationFirewall/socketfilterfw --getglobalstate 2>&1)

    if echo "$FW_STATUS" | grep -q "enabled"; then
        echo -e "${YELLOW}⚠️  Pare-feu activé - peut bloquer les connexions réseau${NC}"
        echo ""
        read -p "Voulez-vous désactiver le pare-feu temporairement ? (o/N) " -n 1 -r
        echo ""

        if [[ $REPLY =~ ^[Oo]$ ]]; then
            sudo /usr/libexec/ApplicationFirewall/socketfilterfw --setglobalstate off
            echo -e "${GREEN}✅ Pare-feu désactivé${NC}"
        else
            echo -e "${YELLOW}⚠️  Le lecteur RFID risque de ne pas pouvoir se connecter${NC}"
        fi
    else
        echo -e "${GREEN}✅ Pare-feu désactivé${NC}"
    fi
    echo ""
fi

# Démarrer le serveur
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  🚀 DÉMARRAGE DU SERVEUR"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${GREEN}Serveur Laravel en cours de démarrage...${NC}"
echo -e "${YELLOW}Appuyez sur Ctrl+C pour arrêter le serveur${NC}"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Démarrer avec output_buffering désactivé et host 0.0.0.0 pour accès réseau
php -d output_buffering=Off artisan serve --host=0.0.0.0 --port=8000
