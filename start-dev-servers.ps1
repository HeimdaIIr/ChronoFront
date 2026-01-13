# ChronoFront - Dev Servers Launcher
# Launches TWO PHP servers to avoid SSE blocking

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  ChronoFront - Dual Server Mode" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Starting TWO PHP servers:" -ForegroundColor Yellow
Write-Host "  [1] Main App    -> http://localhost:8000" -ForegroundColor Green
Write-Host "  [2] SSE Server  -> http://localhost:8001" -ForegroundColor Green
Write-Host ""
Write-Host "This avoids SSE blocking the main server!" -ForegroundColor Yellow
Write-Host ""
Write-Host "Press Ctrl+C to stop both servers" -ForegroundColor Red
Write-Host ""

# Kill any existing PHP servers on these ports
Write-Host "Cleaning up existing servers..." -ForegroundColor Gray
Get-Process php -ErrorAction SilentlyContinue | Where-Object {
    $_.MainWindowTitle -like "*artisan*" -or $_.CommandLine -like "*artisan*"
} | Stop-Process -Force -ErrorAction SilentlyContinue

Start-Sleep -Seconds 1

# Start Main Server (port 8000)
Write-Host "[1] Starting Main Server on port 8000..." -ForegroundColor Green
$mainServer = Start-Process -FilePath "php" -ArgumentList "artisan", "serve", "--host=0.0.0.0", "--port=8000" -PassThru -WindowStyle Normal

Start-Sleep -Seconds 2

# Start SSE Server (port 8001)
Write-Host "[2] Starting SSE Server on port 8001..." -ForegroundColor Green
$sseServer = Start-Process -FilePath "php" -ArgumentList "artisan", "serve", "--host=0.0.0.0", "--port=8001" -PassThru -WindowStyle Normal

Start-Sleep -Seconds 2

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  SERVERS RUNNING!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Main App:    http://localhost:8000" -ForegroundColor White
Write-Host "SSE Stream:  http://localhost:8001/api/rfid/live-stream" -ForegroundColor White
Write-Host ""
Write-Host "RFID Live:   http://localhost:8000/rfidlive-ultra" -ForegroundColor Yellow
Write-Host ""
Write-Host "Press Ctrl+C to stop both servers..." -ForegroundColor Red
Write-Host ""

# Wait for Ctrl+C
try {
    while ($true) {
        Start-Sleep -Seconds 1

        # Check if servers are still running
        if ($mainServer.HasExited) {
            Write-Host "Main server stopped!" -ForegroundColor Red
            break
        }
        if ($sseServer.HasExited) {
            Write-Host "SSE server stopped!" -ForegroundColor Red
            break
        }
    }
} finally {
    Write-Host ""
    Write-Host "Stopping servers..." -ForegroundColor Yellow

    # Stop both servers
    if (-not $mainServer.HasExited) {
        Stop-Process -Id $mainServer.Id -Force -ErrorAction SilentlyContinue
    }
    if (-not $sseServer.HasExited) {
        Stop-Process -Id $sseServer.Id -Force -ErrorAction SilentlyContinue
    }

    Write-Host "Servers stopped." -ForegroundColor Green
}
