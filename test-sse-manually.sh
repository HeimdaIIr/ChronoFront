#!/bin/bash

echo "========================================="
echo "  Test Manuel du SSE"
echo "========================================="
echo ""

echo "1️⃣  Test de connexion au SSE sur port 8001..."
echo ""
echo "Connexion à: http://localhost:8001/api/rfid/live-stream"
echo ""
echo "Vous devriez voir 'retry: 1000' apparaître..."
echo "Appuyez sur Ctrl+C pour arrêter le test."
echo ""
echo "========================================="
echo ""

# Start SSE connection in background
curl -N http://localhost:8001/api/rfid/live-stream &
CURL_PID=$!

# Wait 2 seconds
sleep 2

# Check if curl is still running
if ps -p $CURL_PID > /dev/null 2>&1; then
    echo ""
    echo "✅ Le SSE est connecté et actif!"
    echo ""
    echo "Maintenant, dans un AUTRE terminal, envoyez une détection:"
    echo ""
    echo "  ./test-manual.sh"
    echo ""
    echo "Vous devriez voir apparaître ici:"
    echo "  event: detection"
    echo "  data: {...}"
    echo ""
    echo "Appuyez sur Ctrl+C quand vous avez terminé."
    echo ""

    # Wait for user to press Ctrl+C
    wait $CURL_PID
else
    echo ""
    echo "❌ Le SSE s'est déconnecté immédiatement!"
    echo ""
    echo "Cela signifie que le serveur sur port 8001 a un problème."
    echo ""
    echo "Vérifiez les logs du serveur sur port 8001."
    echo ""
fi
