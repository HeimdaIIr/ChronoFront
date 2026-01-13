#!/bin/bash

echo "========================================="
echo "  Vérification de la configuration"
echo "========================================="
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ Fichier .env introuvable!"
    echo "   Copiez .env.example vers .env:"
    echo "   cp .env.example .env"
    echo ""
    exit 1
fi

# Check SSE_SERVER_URL
echo "1️⃣  Configuration SSE"
SSE_URL=$(grep "^SSE_SERVER_URL=" .env | cut -d= -f2)
if [ -z "$SSE_URL" ]; then
    echo "⚠️  SSE_SERVER_URL non défini dans .env"
    echo "   Ajoutez: SSE_SERVER_URL=http://localhost:8001"
    echo ""
    echo "Voulez-vous que je l'ajoute maintenant? (y/n)"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        echo "SSE_SERVER_URL=http://localhost:8001" >> .env
        echo "✅ SSE_SERVER_URL ajouté!"
    fi
else
    echo "✅ SSE_SERVER_URL = $SSE_URL"
fi
echo ""

# Check if servers are running
echo "2️⃣  État des serveurs"
if lsof -i :8000 >/dev/null 2>&1; then
    PID_8000=$(lsof -ti :8000)
    echo "✅ Serveur principal (port 8000) actif - PID: $PID_8000"
else
    echo "❌ Serveur principal (port 8000) INACTIF"
    echo "   Lancez: ./start-dev-servers.sh"
fi

if lsof -i :8001 >/dev/null 2>&1; then
    PID_8001=$(lsof -ti :8001)
    echo "✅ Serveur SSE (port 8001) actif - PID: $PID_8001"
else
    echo "❌ Serveur SSE (port 8001) INACTIF"
    echo "   Lancez: ./start-dev-servers.sh"
fi
echo ""

# Check IP
echo "3️⃣  Adresses IP"
WIFI_IP=$(ipconfig getifaddr en0 2>/dev/null)
ETH_IP=$(ipconfig getifaddr en1 2>/dev/null)

if [ ! -z "$WIFI_IP" ]; then
    echo "📶 WiFi: $WIFI_IP"
fi
if [ ! -z "$ETH_IP" ]; then
    echo "🔌 Ethernet: $ETH_IP"
fi
echo ""

# Check cache directory
echo "4️⃣  Répertoire de cache"
if [ -d storage/framework/cache ]; then
    echo "✅ Répertoire de cache existe"
    echo "   Permissions: $(ls -ld storage/framework/cache | awk '{print $1}')"
else
    echo "❌ Répertoire de cache introuvable"
    echo "   Créez-le: mkdir -p storage/framework/cache/data"
fi
echo ""

# Test endpoint
echo "5️⃣  Test de l'endpoint debug"
RESPONSE=$(curl -s -X POST http://localhost:8000/api/rfid/debug \
    -H "Content-Type: application/json" \
    -d '{"test":"config_check"}' 2>&1)

if echo "$RESPONSE" | grep -q "success"; then
    echo "✅ Endpoint /api/rfid/debug répond correctement"
else
    echo "❌ Endpoint /api/rfid/debug ne répond pas"
    echo "   Réponse: $RESPONSE"
fi
echo ""

# Summary
echo "========================================="
echo "  Résumé"
echo "========================================="
echo ""

if lsof -i :8000 >/dev/null 2>&1 && lsof -i :8001 >/dev/null 2>&1; then
    echo "✅ Tout semble prêt!"
    echo ""
    echo "Prochaines étapes:"
    echo "  1. Ouvrez: http://localhost:8000/rfidlive-ultra"
    echo "  2. Lancez: ./test-manual.sh"
    echo "  3. Vérifiez qu'une détection TEST_MANUAL apparaît"
    echo ""
    if [ ! -z "$WIFI_IP" ]; then
        echo "Pour votre lecteur portable:"
        echo "  URL: http://$WIFI_IP:8000/api/rfid/debug"
    fi
else
    echo "❌ Les serveurs ne tournent pas"
    echo ""
    echo "Lancez: ./start-dev-servers.sh"
fi
echo ""
