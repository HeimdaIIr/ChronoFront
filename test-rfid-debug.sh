#!/bin/bash

echo "========================================="
echo "  Test RFID Debug Endpoint"
echo "========================================="
echo ""

# Get IP
IP=$(ipconfig getifaddr en0 2>/dev/null)
if [ -z "$IP" ]; then
    IP="localhost"
fi

echo "Testing on IP: $IP"
echo ""

# Test 1: POST
echo "1️⃣  Testing POST method..."
RESULT_POST=$(curl -s -X POST http://$IP:8000/api/rfid/debug \
    -H "Content-Type: application/json" \
    -H "reader-id: 200" \
    -d '{"test":"POST method","tag":"2000001"}')

if echo "$RESULT_POST" | grep -q "success"; then
    echo "✅ POST works!"
else
    echo "❌ POST failed"
    echo "Response: $RESULT_POST"
fi
echo ""

# Test 2: PUT
echo "2️⃣  Testing PUT method..."
RESULT_PUT=$(curl -s -X PUT http://$IP:8000/api/rfid/debug \
    -H "Content-Type: application/json" \
    -H "reader-id: 200" \
    -d '{"test":"PUT method","tag":"2000002"}')

if echo "$RESULT_PUT" | grep -q "success"; then
    echo "✅ PUT works!"
else
    echo "❌ PUT failed"
    echo "Response: $RESULT_PUT"
fi
echo ""

# Test 3: GET
echo "3️⃣  Testing GET method..."
RESULT_GET=$(curl -s -X GET "http://$IP:8000/api/rfid/debug?tag=2000003&reader_id=200")

if echo "$RESULT_GET" | grep -q "success"; then
    echo "✅ GET works!"
else
    echo "❌ GET failed"
    echo "Response: $RESULT_GET"
fi
echo ""

# Test 4: Check if servers are running
echo "4️⃣  Checking if servers are running..."
if lsof -i :8000 >/dev/null 2>&1; then
    echo "✅ Port 8000 is listening"
else
    echo "❌ Port 8000 is NOT listening - Run: ./start-dev-servers.sh"
fi

if lsof -i :8001 >/dev/null 2>&1; then
    echo "✅ Port 8001 is listening"
else
    echo "❌ Port 8001 is NOT listening - Run: ./start-dev-servers.sh"
fi
echo ""

# Test 5: Check Laravel logs
echo "5️⃣  Checking Laravel logs (last 5 lines)..."
if [ -f storage/logs/laravel.log ]; then
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    tail -n 5 storage/logs/laravel.log
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
else
    echo "⚠️  No log file found yet"
fi
echo ""

# Instructions
echo "========================================="
echo "  Next Steps"
echo "========================================="
echo ""
echo "If all tests passed:"
echo "  1. Open http://localhost:8000/rfidlive-ultra"
echo "  2. You should see 3 test requests (POST, PUT, GET)"
echo "  3. Configure your reader to: http://$IP:8000/api/rfid/debug"
echo ""
echo "If tests failed:"
echo "  1. Make sure servers are running: ./start-dev-servers.sh"
echo "  2. Check firewall settings (System Preferences > Security)"
echo "  3. Check that you're using the correct IP"
echo ""
