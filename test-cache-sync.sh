#!/bin/bash

echo "========================================="
echo "  Test Synchronisation Cache"
echo "========================================="
echo ""
echo "Ce test vérifie que les 2 serveurs (8000 et 8001)"
echo "utilisent bien le MÊME cache."
echo ""

# 1. Clear cache first
echo "1️⃣  Vidage du cache..."
curl -s -X POST http://localhost:8000/api/rfid/clear-logs > /dev/null
echo "✅ Cache vidé"
echo ""

# 2. Check cache is empty on both servers
echo "2️⃣  Vérification que le cache est vide sur les 2 serveurs..."

COUNT_8000=$(curl -s http://localhost:8000/api/rfid/raw-logs | grep -o '"count":[0-9]*' | cut -d: -f2)
COUNT_8001=$(curl -s http://localhost:8001/api/rfid/raw-logs | grep -o '"count":[0-9]*' | cut -d: -f2)

echo "   Port 8000: $COUNT_8000 logs"
echo "   Port 8001: $COUNT_8001 logs"

if [ "$COUNT_8000" != "0" ] || [ "$COUNT_8001" != "0" ]; then
    echo "⚠️  Le cache n'est pas vide, mais continuons..."
fi
echo ""

# 3. Send detection to port 8000
echo "3️⃣  Envoi d'une détection au port 8000..."
curl -s -X POST http://localhost:8000/api/rfid/debug \
    -H "Content-Type: application/json" \
    -H "reader-id: 200" \
    -d '{"tag":"CACHE_SYNC_TEST","timestamp":9999}' > /dev/null
echo "✅ Détection envoyée"
echo ""

# Wait a bit for cache to write
sleep 1

# 4. Check if both servers see the same cache
echo "4️⃣  Vérification que les 2 serveurs voient la détection..."

# Check port 8000
RESPONSE_8000=$(curl -s http://localhost:8000/api/rfid/raw-logs)
if echo "$RESPONSE_8000" | grep -q "CACHE_SYNC_TEST"; then
    echo "✅ Port 8000 VOIT la détection dans le cache"
else
    echo "❌ Port 8000 NE VOIT PAS la détection dans le cache"
fi

# Check port 8001
RESPONSE_8001=$(curl -s http://localhost:8001/api/rfid/raw-logs)
if echo "$RESPONSE_8001" | grep -q "CACHE_SYNC_TEST"; then
    echo "✅ Port 8001 VOIT la détection dans le cache"
else
    echo "❌ Port 8001 NE VOIT PAS la détection dans le cache"
    echo ""
    echo "🚨 PROBLÈME TROUVÉ!"
    echo ""
    echo "Les 2 serveurs n'utilisent PAS le même cache!"
    echo ""
    echo "Causes possibles:"
    echo "  1. Les serveurs ne sont pas lancés depuis le même répertoire"
    echo "  2. Les serveurs utilisent des configs différentes"
    echo "  3. Problème de permissions sur storage/"
    echo ""
    echo "Solution:"
    echo "  1. Arrêtez les serveurs (Ctrl+C dans les terminaux)"
    echo "  2. Relancez depuis CE répertoire:"
    echo "     ./start-dev-servers.sh"
    echo ""
    exit 1
fi
echo ""

echo "========================================="
echo "  Résultat"
echo "========================================="
echo ""
echo "✅ Les 2 serveurs partagent bien le MÊME cache!"
echo ""
echo "Le problème ne vient donc PAS du cache."
echo ""
echo "Testons maintenant le SSE manuellement:"
echo "  ./test-sse-manually.sh"
echo ""
