#!/bin/bash

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  🔍 DIAGNOSTIC RÉSEAU - ChronoFront RFID"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Détection de l'IP
echo "📍 1. DÉTECTION IP RÉSEAU"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
IP_EN0=$(ipconfig getifaddr en0 2>/dev/null)
IP_EN1=$(ipconfig getifaddr en1 2>/dev/null)

if [ -n "$IP_EN0" ]; then
    echo -e "${GREEN}✅ Interface en0 (WiFi): $IP_EN0${NC}"
    NETWORK_IP="$IP_EN0"
elif [ -n "$IP_EN1" ]; then
    echo -e "${GREEN}✅ Interface en1: $IP_EN1${NC}"
    NETWORK_IP="$IP_EN1"
else
    echo -e "${RED}❌ Aucune IP réseau détectée${NC}"
    NETWORK_IP=""
fi

echo ""

# Statut du pare-feu
echo "🛡️  2. STATUT DU PARE-FEU"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
FW_STATUS=$(sudo /usr/libexec/ApplicationFirewall/socketfilterfw --getglobalstate 2>&1)

if echo "$FW_STATUS" | grep -q "disabled"; then
    echo -e "${GREEN}✅ Pare-feu DÉSACTIVÉ (accès réseau autorisé)${NC}"
elif echo "$FW_STATUS" | grep -q "enabled"; then
    echo -e "${YELLOW}⚠️  Pare-feu ACTIVÉ (peut bloquer l'accès réseau)${NC}"
    echo -e "${BLUE}   Pour le désactiver: sudo /usr/libexec/ApplicationFirewall/socketfilterfw --setglobalstate off${NC}"
else
    echo -e "${YELLOW}⚠️  Statut du pare-feu inconnu (sudo requis)${NC}"
fi

echo ""

# Ports en écoute
echo "🔌 3. SERVEURS EN COURS D'EXÉCUTION"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

PORT_8000=$(lsof -i :8000 2>/dev/null)
if [ -n "$PORT_8000" ]; then
    echo -e "${GREEN}✅ Port 8000 : EN ÉCOUTE${NC}"
    echo "$PORT_8000" | grep LISTEN | awk '{print "   Process: " $1 " (PID: " $2 ")"}'

    # Vérifie si c'est sur 0.0.0.0 ou localhost
    if echo "$PORT_8000" | grep -q "\*:8000"; then
        echo -e "${GREEN}   ✅ Écoute sur TOUTES les interfaces (0.0.0.0) - Accessible réseau${NC}"
    elif echo "$PORT_8000" | grep -q "localhost:8000"; then
        echo -e "${RED}   ❌ Écoute UNIQUEMENT sur localhost - NON accessible réseau${NC}"
        echo -e "${BLUE}   Solution: Redémarrer avec --host=0.0.0.0${NC}"
    fi
else
    echo -e "${RED}❌ Port 8000 : AUCUN SERVEUR${NC}"
    echo -e "${BLUE}   Démarrer avec: php artisan serve --host=0.0.0.0 --port=8000${NC}"
fi

echo ""

# Tests de connectivité
echo "🧪 4. TESTS DE CONNECTIVITÉ"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test localhost
echo -n "Test localhost:8000... "
LOCALHOST_TEST=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/rfid/debug 2>/dev/null)

if [ "$LOCALHOST_TEST" = "200" ] || [ "$LOCALHOST_TEST" = "405" ]; then
    echo -e "${GREEN}✅ OK (HTTP $LOCALHOST_TEST)${NC}"
else
    echo -e "${RED}❌ ÉCHEC${NC}"
    if [ -z "$PORT_8000" ]; then
        echo -e "${BLUE}   Le serveur n'est pas démarré${NC}"
    fi
fi

# Test IP réseau
if [ -n "$NETWORK_IP" ]; then
    echo -n "Test $NETWORK_IP:8000... "
    NETWORK_TEST=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 3 http://$NETWORK_IP:8000/api/rfid/debug 2>/dev/null)

    if [ "$NETWORK_TEST" = "200" ] || [ "$NETWORK_TEST" = "405" ]; then
        echo -e "${GREEN}✅ OK (HTTP $NETWORK_TEST) - Accessible depuis le réseau!${NC}"
    else
        echo -e "${RED}❌ ÉCHEC${NC}"
        if echo "$PORT_8000" | grep -q "localhost:8000"; then
            echo -e "${BLUE}   Le serveur écoute sur localhost seulement (pas 0.0.0.0)${NC}"
        elif echo "$FW_STATUS" | grep -q "enabled"; then
            echo -e "${BLUE}   Le pare-feu est activé, il bloque probablement l'accès${NC}"
        fi
    fi
fi

echo ""

# Test avec le lecteur RFID
echo "📡 5. TEST DÉTECTION RFID"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ "$LOCALHOST_TEST" = "200" ] || [ "$LOCALHOST_TEST" = "405" ]; then
    echo "Envoi d'une détection de test..."

    TEST_RESPONSE=$(curl -s -X PUT http://localhost:8000/api/rfid/debug \
        -H "Content-Type: application/json" \
        -H "Serial: 200" \
        -d '[{"serial":"TEST_'$(date +%s)'"}]' 2>/dev/null)

    if echo "$TEST_RESPONSE" | grep -q '"success":true'; then
        echo -e "${GREEN}✅ Détection envoyée avec succès!${NC}"
        echo -e "${BLUE}   Vérifie http://localhost:8000/rfidlive-ultra${NC}"
    else
        echo -e "${RED}❌ Échec de l'envoi${NC}"
        echo "   Réponse: $TEST_RESPONSE"
    fi
else
    echo -e "${YELLOW}⚠️  Impossible de tester (serveur non accessible)${NC}"
fi

echo ""

# Résumé et instructions
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  📋 RÉSUMÉ"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ -n "$NETWORK_IP" ]; then
    echo -e "${GREEN}Configuration du lecteur RFID (reader_id: 200):${NC}"
    echo "  URL: http://$NETWORK_IP:8000/api/rfid/debug"
    echo "  Méthode: PUT"
    echo "  Port: 8000"
    echo ""
fi

echo -e "${BLUE}Commande pour démarrer le serveur correctement:${NC}"
echo "  php -d output_buffering=Off artisan serve --host=0.0.0.0 --port=8000"
echo ""

echo -e "${BLUE}Page de monitoring en direct:${NC}"
if [ -n "$NETWORK_IP" ]; then
    echo "  Local:  http://localhost:8000/rfidlive-ultra"
    echo "  Réseau: http://$NETWORK_IP:8000/rfidlive-ultra"
else
    echo "  http://localhost:8000/rfidlive-ultra"
fi
echo ""

# Checklist
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  ✓ CHECKLIST"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -n "$NETWORK_IP" ]; then
    echo -e "${GREEN}✅${NC} IP réseau détectée"
else
    echo -e "${RED}❌${NC} IP réseau non détectée"
fi

if echo "$FW_STATUS" | grep -q "disabled"; then
    echo -e "${GREEN}✅${NC} Pare-feu désactivé"
else
    echo -e "${RED}❌${NC} Pare-feu activé (peut bloquer)"
fi

if [ -n "$PORT_8000" ]; then
    if echo "$PORT_8000" | grep -q "\*:8000"; then
        echo -e "${GREEN}✅${NC} Serveur en écoute sur 0.0.0.0"
    else
        echo -e "${RED}❌${NC} Serveur en écoute sur localhost seulement"
    fi
else
    echo -e "${RED}❌${NC} Serveur non démarré"
fi

if [ "$LOCALHOST_TEST" = "200" ] || [ "$LOCALHOST_TEST" = "405" ]; then
    echo -e "${GREEN}✅${NC} API accessible en local"
else
    echo -e "${RED}❌${NC} API non accessible en local"
fi

if [ -n "$NETWORK_IP" ]; then
    if [ "$NETWORK_TEST" = "200" ] || [ "$NETWORK_TEST" = "405" ]; then
        echo -e "${GREEN}✅${NC} API accessible depuis le réseau"
    else
        echo -e "${RED}❌${NC} API non accessible depuis le réseau"
    fi
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Instructions finales
if [ -z "$NETWORK_IP" ] || [ "$NETWORK_TEST" != "200" ] && [ "$NETWORK_TEST" != "405" ]; then
    echo -e "${YELLOW}⚠️  ACTION REQUISE:${NC}"
    echo ""

    if [ -z "$PORT_8000" ]; then
        echo "1. Démarrer le serveur:"
        echo -e "   ${BLUE}php -d output_buffering=Off artisan serve --host=0.0.0.0 --port=8000${NC}"
        echo ""
    fi

    if echo "$FW_STATUS" | grep -q "enabled"; then
        echo "2. Désactiver le pare-feu:"
        echo -e "   ${BLUE}sudo /usr/libexec/ApplicationFirewall/socketfilterfw --setglobalstate off${NC}"
        echo ""
    fi

    echo "Puis relance ce script pour vérifier."
    echo ""
else
    echo -e "${GREEN}🎉 Tout est prêt! Le lecteur RFID peut maintenant se connecter.${NC}"
    echo ""
fi
