#!/bin/bash

echo "========================================="
echo "  Diagnostic SSE (Server-Sent Events)"
echo "  Version macOS"
echo "========================================="
echo ""

# 1. Check if port 8001 is listening
echo "1️⃣  Vérification du serveur SSE (port 8001)..."
if lsof -i :8001 >/dev/null 2>&1; then
    PID=$(lsof -ti :8001 | head -n 1)
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

# 2. Check .env SSE_SERVER_URL
echo "2️⃣  Vérification de la configuration .env..."
if [ -f .env ]; then
    SSE_URL=$(grep "^SSE_SERVER_URL=" .env | cut -d= -f2)
    if [ -z "$SSE_URL" ]; then
        echo "❌ PROBLÈME : SSE_SERVER_URL n'est PAS défini dans .env"
        echo ""
        echo "   SOLUTION : J'ajoute cette ligne dans .env maintenant..."
        echo "SSE_SERVER_URL=http://localhost:8001" >> .env
        echo "   ✅ SSE_SERVER_URL ajouté!"
        echo ""
        echo "   👉 RECHARGEZ la page web (Cmd+R) pour que ça prenne effet!"
        echo ""
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

# 3. Check cache
echo "3️⃣  Vérification du cache RFID..."
CACHE_RESPONSE=$(curl -s http://localhost:8000/api/rfid/raw-logs)

if echo "$CACHE_RESPONSE" | grep -q "success"; then
    LOG_COUNT=$(echo "$CACHE_RESPONSE" | grep -o '"count":[0-9]*' | cut -d: -f2)
    echo "✅ API de cache répond - $LOG_COUNT logs en cache"
else
    echo "⚠️  API de cache ne répond pas correctement"
    echo "   Réponse: $CACHE_RESPONSE"
fi
echo ""

# 4. Send a test detection
echo "4️⃣  Envoi d'une détection de TEST..."
RESPONSE=$(curl -s -X POST http://localhost:8000/api/rfid/debug \
    -H "Content-Type: application/json" \
    -H "reader-id: 200" \
    -d '{"tag":"SSE_DIAGNOSTIC_TEST","timestamp":1234567890}')

if echo "$RESPONSE" | grep -q "success"; then
    echo "✅ Détection envoyée avec succès"

    sleep 1

    # Check if it's in cache
    CACHE_AFTER=$(curl -s http://localhost:8000/api/rfid/raw-logs)

    if echo "$CACHE_AFTER" | grep -q "SSE_DIAGNOSTIC_TEST"; then
        echo "✅ Détection est dans le CACHE"
    else
        echo "❌ PROBLÈME : Détection PAS dans le cache"
        echo ""
        echo "   Vérifiez les permissions : chmod -R 775 storage/"
    fi
else
    echo "❌ PROBLÈME : Échec d'envoi"
fi
echo ""

# 5. Test SSE manually
echo "5️⃣  Test manuel du SSE..."
echo "   Pour tester le SSE, ouvrez un autre terminal et lancez:"
echo ""
echo "   curl -N http://localhost:8001/api/rfid/live-stream"
echo ""
echo "   Vous devriez voir: retry: 1000"
echo ""
echo "   Puis dans ce terminal, envoyez une détection:"
echo "   curl -X POST http://localhost:8000/api/rfid/debug -H 'reader-id: 200' -d '{\"tag\":\"TEST\"}'"
echo ""
echo "   Le SSE devrait afficher: event: detection"
echo ""

echo "========================================="
echo "  RÉSUMÉ"
echo "========================================="
echo ""

# Check what's wrong
HAS_ERROR=0

if ! lsof -i :8001 >/dev/null 2>&1; then
    echo "❌ Port 8001 n'écoute pas"
    HAS_ERROR=1
fi

if [ -f .env ]; then
    SSE_URL=$(grep "^SSE_SERVER_URL=" .env | cut -d= -f2)
    if [ -z "$SSE_URL" ]; then
        echo "❌ SSE_SERVER_URL manquant dans .env (maintenant ajouté!)"
        HAS_ERROR=1
    fi
fi

if [ $HAS_ERROR -eq 0 ]; then
    echo "✅ Configuration semble correcte!"
    echo ""
    echo "PROCHAINES ÉTAPES:"
    echo ""
    echo "1. Ouvrez http://localhost:8000/rfidlive-ultra"
    echo ""
    echo "2. Vérifiez le STATUT en haut à droite:"
    echo "   • Si '● Connecté' (vert) → Bon signe!"
    echo "   • Si '● Déconnecté' (rouge) → Problème de connexion SSE"
    echo ""
    echo "3. Ouvrez la CONSOLE du navigateur (Cmd+Option+J)"
    echo "   Cherchez des erreurs EventSource ou SSE"
    echo ""
    echo "4. Envoyez une détection de test:"
    echo "   ./test-manual.sh"
    echo ""
    echo "5. La détection devrait apparaître dans rfidlive-ultra!"
    echo ""
    echo "Si rien n'apparaît encore:"
    echo "   • Rechargez la page (Cmd+R)"
    echo "   • Vérifiez la console navigateur"
    echo "   • Regardez les logs: tail -f storage/logs/laravel.log"
else
    echo ""
    echo "⚠️  Des problèmes ont été détectés ci-dessus"
    echo "   Corrigez-les puis réessayez"
fi
echo ""
