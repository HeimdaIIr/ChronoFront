#!/bin/bash

echo "========================================="
echo "  Fix SSE Connection - Étape par étape"
echo "========================================="
echo ""

# 1. Clear Laravel config cache
echo "1️⃣  Vidage du cache de configuration Laravel..."
php artisan config:clear
php artisan cache:clear
echo "✅ Cache vidé"
echo ""

# 2. Check .env
echo "2️⃣  Vérification de .env..."
if grep -q "^SSE_SERVER_URL=" .env; then
    SSE_URL=$(grep "^SSE_SERVER_URL=" .env | cut -d= -f2)
    echo "✅ SSE_SERVER_URL = $SSE_URL"
else
    echo "⚠️  SSE_SERVER_URL manquant, ajout..."
    echo "SSE_SERVER_URL=http://localhost:8001" >> .env
    echo "✅ Ajouté!"
fi
echo ""

# 3. Check both servers are running
echo "3️⃣  Vérification des serveurs..."
PORT_8000=$(lsof -ti :8000 2>/dev/null | wc -l | tr -d ' ')
PORT_8001=$(lsof -ti :8001 2>/dev/null | wc -l | tr -d ' ')

if [ "$PORT_8000" -gt 0 ]; then
    echo "✅ Serveur principal (8000) actif"
else
    echo "❌ Serveur principal (8000) INACTIF"
    echo "   Lancez: ./start-dev-servers.sh"
    exit 1
fi

if [ "$PORT_8001" -gt 0 ]; then
    echo "✅ Serveur SSE (8001) actif"
else
    echo "❌ Serveur SSE (8001) INACTIF"
    echo "   Lancez: ./start-dev-servers.sh"
    exit 1
fi
echo ""

# 4. Test SSE endpoint directly
echo "4️⃣  Test direct du SSE endpoint..."
echo "   Connexion à http://localhost:8001/api/rfid/live-stream..."

# Use gtimeout if available (brew install coreutils), otherwise perl
if command -v gtimeout >/dev/null 2>&1; then
    RESPONSE=$(gtimeout 2 curl -N -s http://localhost:8001/api/rfid/live-stream 2>&1 | head -n 1)
elif command -v perl >/dev/null 2>&1; then
    RESPONSE=$(perl -e 'alarm 2; exec @ARGV' curl -N -s http://localhost:8001/api/rfid/live-stream 2>&1 | head -n 1)
else
    # Fallback: just try curl
    RESPONSE=$(curl -N -s http://localhost:8001/api/rfid/live-stream 2>&1 | head -n 1) &
    CURL_PID=$!
    sleep 1
    kill $CURL_PID 2>/dev/null
    wait $CURL_PID 2>/dev/null
fi

if echo "$RESPONSE" | grep -q "retry"; then
    echo "✅ SSE endpoint répond correctement"
    echo "   Réponse: $RESPONSE"
else
    echo "⚠️  SSE endpoint réponse inattendue: $RESPONSE"
fi
echo ""

# 5. Instructions
echo "========================================="
echo "  ACTIONS À FAIRE MAINTENANT"
echo "========================================="
echo ""
echo "1️⃣  Ouvrez la console développeur de votre navigateur:"
echo "   • Chrome/Safari: Cmd+Option+J"
echo "   • Ou clic droit > Inspecter > Console"
echo ""
echo "2️⃣  Faites un HARD REFRESH de la page:"
echo "   • Cmd+Shift+R (ou Cmd+Option+R)"
echo "   • Ceci va vider le cache du navigateur"
echo ""
echo "3️⃣  Dans la console, cherchez des erreurs comme:"
echo "   • EventSource failed"
echo "   • Failed to load resource"
echo "   • net::ERR_CONNECTION_REFUSED"
echo ""
echo "4️⃣  Vérifiez le statut dans /rfidlive-ultra:"
echo "   • Devrait être '● Connecté' (vert)"
echo "   • Si toujours rouge, regardez la console"
echo ""
echo "5️⃣  Testez une détection:"
echo "   ./test-manual.sh"
echo ""
echo "========================================="
echo ""

# Display console command to check SSE
echo "Pour tester le SSE manuellement:"
echo ""
echo "curl -N http://localhost:8001/api/rfid/live-stream"
echo ""
echo "(Vous devriez voir 'retry: 1000')"
echo ""
