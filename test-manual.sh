#!/bin/bash

echo "========================================="
echo "  Test Manuel - Envoi d'une détection"
echo "========================================="
echo ""

# Get IP
IP=$(ipconfig getifaddr en0 2>/dev/null)
if [ -z "$IP" ]; then
    IP="localhost"
fi

echo "📍 IP détectée: $IP"
echo ""

# Test avec localhost d'abord
echo "1️⃣  Envoi d'une détection de TEST vers localhost..."
echo ""

RESPONSE=$(curl -s -X POST http://localhost:8000/api/rfid/debug \
    -H "Content-Type: application/json" \
    -H "reader-id: 200" \
    -d '{"tag":"TEST_MANUAL","timestamp":1234567890}')

echo "Réponse du serveur:"
echo "$RESPONSE" | jq . 2>/dev/null || echo "$RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q "success"; then
    echo "✅ L'endpoint répond correctement!"
    echo ""
    echo "➡️  Maintenant, ouvrez http://localhost:8000/rfidlive-ultra"
    echo "    Vous DEVRIEZ voir cette détection TEST_MANUAL apparaître!"
    echo ""
else
    echo "❌ L'endpoint ne répond pas correctement"
    echo ""
    echo "Vérifiez que les serveurs tournent:"
    echo "  ./start-dev-servers.sh"
    echo ""
fi

echo "========================================="
echo "  Informations de connexion"
echo "========================================="
echo ""
echo "Pour votre LECTEUR PORTABLE, configurez:"
echo ""
echo "  URL: http://$IP:8000/api/rfid/debug"
echo "  Method: POST (ou PUT)"
echo "  Header: reader-id: 200"
echo ""
echo "========================================="
