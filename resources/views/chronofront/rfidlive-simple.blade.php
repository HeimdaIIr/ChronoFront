<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Live - Requêtes brutes</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #0a0a0a;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 20px;
            overflow: hidden;
        }
        .header {
            background: #1a1a1a;
            border: 2px solid #00ff00;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 24px;
            color: #00ff00;
        }
        .counter {
            background: #000;
            border: 1px solid #00ff00;
            padding: 10px 20px;
            font-size: 20px;
            font-weight: bold;
        }
        .log-container {
            background: #000;
            border: 2px solid #00ff00;
            height: calc(100vh - 140px);
            overflow-y: auto;
            padding: 15px;
            font-size: 14px;
            line-height: 1.6;
        }
        .log-entry {
            border-bottom: 1px solid #003300;
            padding: 10px 0;
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                background: #003300;
            }
            to {
                opacity: 1;
                background: transparent;
            }
        }
        .log-entry.new {
            background: #003300;
        }
        .timestamp {
            color: #ffff00;
            font-weight: bold;
        }
        .method {
            color: #00ffff;
            font-weight: bold;
        }
        .serial {
            color: #ff00ff;
            font-weight: bold;
        }
        .data {
            color: #00ff00;
            margin-left: 20px;
            white-space: pre-wrap;
        }
        .status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }
        .status-200 {
            background: #28a745;
            color: #fff;
        }
        .status-400 {
            background: #ffc107;
            color: #000;
        }
        .status-404 {
            background: #dc3545;
            color: #fff;
        }
        .controls {
            display: flex;
            gap: 10px;
        }
        .btn {
            background: #1a1a1a;
            color: #00ff00;
            border: 1px solid #00ff00;
            padding: 8px 15px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .btn:hover {
            background: #00ff00;
            color: #000;
        }
        .btn-danger {
            border-color: #ff0000;
            color: #ff0000;
        }
        .btn-danger:hover {
            background: #ff0000;
            color: #fff;
        }
        ::-webkit-scrollbar {
            width: 10px;
        }
        ::-webkit-scrollbar-track {
            background: #0a0a0a;
        }
        ::-webkit-scrollbar-thumb {
            background: #00ff00;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📡 RFID LIVE - Requêtes HTTP Brutes</h1>
        <div class="controls">
            <div class="counter">
                <span id="counter">0</span> requêtes
            </div>
            <button class="btn" onclick="toggleAutoScroll()">
                <span id="scroll-status">🔄 Auto-scroll ON</span>
            </button>
            <button class="btn btn-danger" onclick="clearLogs()">🗑️ Vider</button>
        </div>
    </div>

    <div class="log-container" id="logContainer"></div>

    <script>
        let logs = [];
        let counter = 0;
        let autoScroll = true;
        let lastLogId = 0;

        async function fetchLogs() {
            try {
                const response = await fetch(`/api/rfid/raw-logs?since=${lastLogId}`);
                const data = await response.json();

                if (data.logs && data.logs.length > 0) {
                    data.logs.forEach(log => {
                        addLog(log);
                        if (log.id > lastLogId) {
                            lastLogId = log.id;
                        }
                    });
                }
            } catch (error) {
                console.error('Erreur lors du chargement des logs:', error);
            }
        }

        function addLog(log) {
            const container = document.getElementById('logContainer');
            const entry = document.createElement('div');
            entry.className = 'log-entry new';

            const statusClass = `status-${log.status}`;

            entry.innerHTML = `
                <div>
                    <span class="timestamp">[${log.timestamp}]</span>
                    <span class="method">${log.method}</span>
                    <span class="serial">Serial: ${log.serial || 'N/A'}</span>
                    <span class="status ${statusClass}">${log.status}</span>
                </div>
                <div class="data">${formatData(log.data)}</div>
            `;

            // Insert at the top
            container.insertBefore(entry, container.firstChild);

            // Remove 'new' class after animation
            setTimeout(() => entry.classList.remove('new'), 300);

            // Update counter
            counter++;
            document.getElementById('counter').textContent = counter;

            // Auto-scroll to top
            if (autoScroll) {
                container.scrollTop = 0;
            }

            // Limit to 100 entries
            const entries = container.children;
            if (entries.length > 100) {
                container.removeChild(entries[entries.length - 1]);
            }
        }

        function formatData(data) {
            if (typeof data === 'string') {
                try {
                    return JSON.stringify(JSON.parse(data), null, 2);
                } catch (e) {
                    return data;
                }
            }
            return JSON.stringify(data, null, 2);
        }

        function clearLogs() {
            if (confirm('Vider tous les logs affichés ?')) {
                document.getElementById('logContainer').innerHTML = '';
                counter = 0;
                document.getElementById('counter').textContent = '0';
            }
        }

        function toggleAutoScroll() {
            autoScroll = !autoScroll;
            const btn = document.getElementById('scroll-status');
            btn.textContent = autoScroll ? '🔄 Auto-scroll ON' : '⏸️ Auto-scroll OFF';
        }

        // Poll every second
        setInterval(fetchLogs, 1000);

        // Initial load
        fetchLogs();
    </script>
</body>
</html>
