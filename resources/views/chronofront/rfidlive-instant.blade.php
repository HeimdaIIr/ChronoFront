<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Live - Détections instantanées</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #000;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 20px;
            overflow: hidden;
        }
        .header {
            background: #0a0a0a;
            border: 1px solid #00ff00;
            padding: 10px 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 18px;
            color: #00ff00;
        }
        .counter {
            color: #ffff00;
            font-size: 16px;
            font-weight: bold;
        }
        .log-container {
            background: #000;
            border: 1px solid #00ff00;
            height: calc(100vh - 80px);
            overflow-y: auto;
            padding: 10px;
            font-size: 14px;
            line-height: 1.4;
        }
        .log-entry {
            color: #00ff00;
            white-space: nowrap;
        }
        .puce {
            color: #00ffff;
            font-weight: bold;
        }
        .timestamp {
            color: #ffff00;
        }
        .serial {
            color: #ff00ff;
        }
        .btn {
            background: #0a0a0a;
            color: #00ff00;
            border: 1px solid #00ff00;
            padding: 5px 10px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-left: 10px;
        }
        .btn:hover {
            background: #00ff00;
            color: #000;
        }
        .status {
            color: #999;
            font-size: 12px;
        }
        .status.connected {
            color: #00ff00;
        }
        .status.disconnected {
            color: #ff0000;
        }
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #000;
        }
        ::-webkit-scrollbar-thumb {
            background: #00ff00;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📡 RFID LIVE - Détections instantanées</h1>
        <div>
            <span class="counter">Détections: <span id="counter">0</span></span>
            <span class="status" id="status">● Déconnecté</span>
            <button class="btn" onclick="clearLogs()">Vider</button>
        </div>
    </div>

    <div class="log-container" id="logContainer"></div>

    <script>
        let counter = 0;
        let eventSource = null;

        function connectSSE() {
            // Close existing connection
            if (eventSource) {
                eventSource.close();
            }

            // Connect to SSE endpoint (dedicated server on port 8001)
            const sseUrl = '{{ env("SSE_SERVER_URL", "http://localhost:8000") }}/api/rfid/live-stream';
            console.log('🔌 SSE Connection URL:', sseUrl);
            eventSource = new EventSource(sseUrl);

            eventSource.onopen = function() {
                document.getElementById('status').textContent = '● Connecté';
                document.getElementById('status').className = 'status connected';
            };

            eventSource.onerror = function() {
                document.getElementById('status').textContent = '● Déconnecté';
                document.getElementById('status').className = 'status disconnected';

                // Reconnect after 2 seconds
                setTimeout(() => {
                    connectSSE();
                }, 2000);
            };

            eventSource.addEventListener('detection', function(e) {
                try {
                    const data = JSON.parse(e.data);
                    addLog(data);
                } catch (error) {
                    console.error('Error parsing SSE data:', error);
                }
            });
        }

        function addLog(data) {
            const container = document.getElementById('logContainer');
            const entry = document.createElement('div');
            entry.className = 'log-entry';

            // Extract tag number from data
            let tagNumber = 'N/A';
            if (data.data) {
                try {
                    const jsonData = typeof data.data === 'string' ? JSON.parse(data.data) : data.data;
                    if (Array.isArray(jsonData) && jsonData.length > 0) {
                        tagNumber = jsonData[0].serial || 'N/A';
                    } else if (jsonData.serial) {
                        tagNumber = jsonData.serial;
                    }
                } catch (e) {
                    // Ignore parse errors
                }
            }

            // Extract timestamp
            let timestamp = data.timestamp || new Date().toLocaleTimeString('fr-FR');

            entry.innerHTML = `<span class="puce">[${tagNumber}]</span> <span class="timestamp">[${timestamp}]</span> <span class="serial">[Serial: ${data.serial || 'N/A'}]</span>`;

            // Insert at the top
            container.insertBefore(entry, container.firstChild);

            // Update counter
            counter++;
            document.getElementById('counter').textContent = counter;

            // Limit to 1000 entries to avoid memory issues
            const entries = container.children;
            if (entries.length > 1000) {
                container.removeChild(entries[entries.length - 1]);
            }
        }

        function clearLogs() {
            document.getElementById('logContainer').innerHTML = '';
            counter = 0;
            document.getElementById('counter').textContent = '0';
        }

        // Connect on page load
        connectSSE();

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (eventSource) {
                eventSource.close();
            }
        });
    </script>
</body>
</html>
