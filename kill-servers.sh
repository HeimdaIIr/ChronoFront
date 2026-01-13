#!/bin/bash

echo "========================================="
echo "  Nettoyage des ports 8000 et 8001"
echo "========================================="
echo ""

# Check what's using port 8000
echo "1️⃣  Vérification du port 8000..."
PORT_8000=$(lsof -ti :8000)
if [ ! -z "$PORT_8000" ]; then
    echo "   ⚠️  Port 8000 utilisé par PID: $PORT_8000"
    echo "   Arrêt du processus..."
    kill -9 $PORT_8000
    echo "   ✅ Port 8000 libéré"
else
    echo "   ✅ Port 8000 libre"
fi
echo ""

# Check what's using port 8001
echo "2️⃣  Vérification du port 8001..."
PORT_8001=$(lsof -ti :8001)
if [ ! -z "$PORT_8001" ]; then
    echo "   ⚠️  Port 8001 utilisé par PID: $PORT_8001"
    echo "   Arrêt du processus..."
    kill -9 $PORT_8001
    echo "   ✅ Port 8001 libéré"
else
    echo "   ✅ Port 8001 libre"
fi
echo ""

# Kill all php artisan serve processes to be sure
echo "3️⃣  Arrêt de tous les serveurs Laravel..."
pkill -f "artisan serve" 2>/dev/null
sleep 1
echo "   ✅ Tous les serveurs Laravel arrêtés"
echo ""

echo "========================================="
echo "  Ports libérés!"
echo "========================================="
echo ""
echo "Vous pouvez maintenant relancer les serveurs:"
echo "  ./start-dev-servers.sh"
echo ""
echo "Ou manuellement dans 2 terminaux:"
echo "  Terminal 1: php artisan serve --host=0.0.0.0 --port=8000"
echo "  Terminal 2: php artisan serve --host=0.0.0.0 --port=8001"
echo ""
