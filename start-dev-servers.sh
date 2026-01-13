#!/bin/bash

# ChronoFront - Dev Servers Launcher
# Launches TWO PHP servers to avoid SSE blocking

echo "========================================"
echo "  ChronoFront - Dual Server Mode"
echo "========================================"
echo ""
echo "Starting TWO PHP servers:"
echo "  [1] Main App    -> http://localhost:8000"
echo "  [2] SSE Server  -> http://localhost:8001"
echo ""
echo "This avoids SSE blocking the main server!"
echo ""
echo "Press Ctrl+C to stop both servers"
echo ""

# Kill any existing PHP servers on these ports
echo "Cleaning up existing servers..."
lsof -ti:8000 | xargs kill -9 2>/dev/null
lsof -ti:8001 | xargs kill -9 2>/dev/null

sleep 1

# Start Main Server (port 8000) in background
echo "[1] Starting Main Server on port 8000..."
php artisan serve --host=0.0.0.0 --port=8000 > /dev/null 2>&1 &
MAIN_PID=$!

sleep 2

# Start SSE Server (port 8001) in background
echo "[2] Starting SSE Server on port 8001..."
php artisan serve --host=0.0.0.0 --port=8001 > /dev/null 2>&1 &
SSE_PID=$!

sleep 2

echo ""
echo "========================================"
echo "  SERVERS RUNNING!"
echo "========================================"
echo ""
echo "Main App:    http://localhost:8000"
echo "SSE Stream:  http://localhost:8001/api/rfid/live-stream"
echo ""
echo "RFID Live:   http://localhost:8000/rfidlive-ultra"
echo ""
echo "PIDs: Main=$MAIN_PID, SSE=$SSE_PID"
echo ""
echo "Press Ctrl+C to stop both servers..."
echo ""

# Trap Ctrl+C and cleanup
cleanup() {
    echo ""
    echo "Stopping servers..."
    kill $MAIN_PID 2>/dev/null
    kill $SSE_PID 2>/dev/null
    echo "Servers stopped."
    exit 0
}

trap cleanup INT TERM

# Wait for servers to exit
wait
