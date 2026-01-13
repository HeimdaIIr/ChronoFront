#!/bin/bash

echo "========================================="
echo "  Diagnostic SSE (Server-Sent Events)"
echo "========================================="
echo ""

# 1. Check if port 8001 is listening
echo "1️⃣  Vérification du serveur SSE (port 8001)..."
if lsof -i :8001 >/dev/null 2>&1; then
    PID=$(lsof -ti :8001)
    echo "✅ Serveur SSE actif sur port 8001 (PID: $PID)"
else
    echo "❌ PROBLÈME : Serveur SSE (port 8001) N'EST PAS actif"
    echo ""
    echo "   SOLUTION : Lancez les 2 serveurs avec:"
    echo "   ./start-dev-servers.sh"
    echo ""
    exit 1
fi
echo ""

# 2. Test SSE endpoint
echo "2️⃣  Test de connexion SSE..."
echo "   Tentative de connexion à http://localhost:8001/api/rfid/live-stream..."
echo ""

# Try to connect to SSE (should see "retry: 1000")
timeout 2 curl -N http://localhost:8001/api/rfid/live-stream 2>&1 | head -n 5 &
CURL_PID=$!
sleep 1

if ps -p $CURL_PID > /dev/null 2>&1; then
    echo "✅ SSE répond (connexion établie)"
    kill $CURL_PID 2>/dev/null
else
    echo "❌ PROBLÈME : SSE ne répond pas"
    echo ""
    echo "   Vérifiez les logs du serveur sur port 8001"
    exit 1
fi
echo ""

# 3. Check .env SSE_SERVER_URL
echo "3️⃣  Vérification de la configuration .env..."
if [ -f .env ]; then
    SSE_URL=$(grep "^SSE_SERVER_URL=" .env | cut -d= -f2)
    if [ -z "$SSE_URL" ]; then
        echo "❌ PROBLÈME : SSE_SERVER_URL n'est PAS défini dans .env"
        echo ""
        echo "   SOLUTION : Ajoutez cette ligne dans .env:"
        echo "   echo 'SSE_SERVER_URL=http://localhost:8001' >> .env"
        echo ""
        echo "   Voulez-vous que je l'ajoute maintenant? (y/n)"
        read -r response
        if [[ "$response" =~ ^[Yy]$ ]]; then
            echo "SSE_SERVER_URL=http://localhost:8001" >> .env
            echo "   ✅ SSE_SERVER_URL ajouté! Rechargez la page web."
        fi
    else
        echo "✅ SSE_SERVER_URL = $SSE_URL"
    fi
else
    echo "❌ PROBLÈME : Fichier .env introuvable"
    echo ""
    echo "   SOLUTION :"
    echo "   cp .env.example .env"
    echo "   php artisan key:generate"
    exit 1
fi
echo ""

# 4. Check cache
echo "4️⃣  Vérification du cache RFID..."
if [ -d storage/framework/cache ]; then
    echo "✅ Répertoire de cache existe"

    # Try to read cache via API
    CACHE_RESPONSE=$(curl -s http://localhost:8000/api/rfid/raw-logs)

    if echo "$CACHE_RESPONSE" | grep -q "success"; then
        LOG_COUNT=$(echo "$CACHE_RESPONSE" | grep -o '"count":[0-9]*' | cut -d: -f2)
        echo "✅ API de cache répond - $LOG_COUNT logs en cache"
    else
        echo "⚠️  API de cache ne répond pas correctement"
    fi
else
    echo "❌ PROBLÈME : Répertoire de cache introuvable"
    echo ""
    echo "   SOLUTION :"
    echo "   mkdir -p storage/framework/cache/data"
    echo "   chmod -R 775 storage/"
    exit 1
fi
echo ""

# 5. Send a test detection and verify it's in cache
echo "5️⃣  Test complet : Envoi détection + Vérification cache..."
echo "   Envoi d'une détection de TEST..."

RESPONSE=$(curl -s -X POST http://localhost:8000/api/rfid/debug \
    -H "Content-Type: application/json" \
    -H "reader-id: 200" \
    -d '{"tag":"SSE_TEST","timestamp":1234567890}')

if echo "$RESPONSE" | grep -q "success"; then
    echo "   ✅ Détection envoyée avec succès"

    sleep 1

    # Check if it's in cache now
    CACHE_AFTER=$(curl -s http://localhost:8000/api/rfid/raw-logs)

    if echo "$CACHE_AFTER" | grep -q "SSE_TEST"; then
        echo "   ✅ Détection est bien dans le CACHE"
        echo ""
        echo "   👉 Le cache fonctionne!"
    else
        echo "   ❌ PROBLÈME : Détection PAS dans le cache"
        echo ""
        echo "   La détection a été envoyée mais pas stockée dans le cache."
        echo "   Vérifiez les permissions du dossier storage/"
        exit 1
    fi
else
    echo "   ❌ PROBLÈME : Échec d'envoi de la détection"
    exit 1
fi
echo ""

# 6. Test SSE actually sends the data
echo "6️⃣  Test SSE : Est-ce que le SSE envoie bien les données du cache?"
echo "   Connexion au SSE pendant 3 secondes..."
echo ""

# Connect to SSE and wait for data
timeout 3 curl -N http://localhost:8001/api/rfid/live-stream 2>/dev/null | grep -m 1 "event: detection" > /tmp/sse_test.log &
SSE_PID=$!

sleep 1

# Send another test detection
curl -s -X POST http://localhost:8000/api/rfid/debug \
    -H "Content-Type: application/json" \
    -d '{"tag":"SSE_LIVE_TEST"}' > /dev/null

# Wait for SSE to process
sleep 2

# Check if SSE received the event
if [ -f /tmp/sse_test.log ] && [ -s /tmp/sse_test.log ]; then
    echo "✅ SSE a envoyé un événement 'detection'!"
    echo ""
    echo "   Le SSE fonctionne correctement!"
else
    echo "❌ PROBLÈME : SSE n'a PAS envoyé d'événement"
    echo ""
    echo "   Le SSE se connecte mais ne lit pas le cache correctement."
    echo "   Vérifiez que les 2 serveurs utilisent le même répertoire."
fi

# Cleanup
kill $SSE_PID 2>/dev/null
rm -f /tmp/sse_test.log

echo ""
echo "========================================="
echo "  RÉSUMÉ DU DIAGNOSTIC"
echo "========================================="
echo ""
echo "Si tout est ✅ ci-dessus, ouvrez :"
echo "  http://localhost:8000/rfidlive-ultra"
echo ""
echo "Et vérifiez que :"
echo "  1. Le statut affiche '● Connecté' (vert)"
echo "  2. Des détections TEST apparaissent"
echo ""
echo "Si le statut est '● Déconnecté' (rouge) :"
echo "  1. Rechargez la page (Cmd+R)"
echo "  2. Vérifiez la console navigateur (F12)"
echo "  3. Vérifiez que SSE_SERVER_URL est dans .env"
echo ""
