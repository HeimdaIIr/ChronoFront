<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Live Ultra</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #000;
            color: #0f0;
            font-family: 'Courier New', monospace;
            overflow: hidden;
        }
        .header {
            background: #111;
            border-bottom: 1px solid #0f0;
            padding: 8px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 40px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
        }
        .stats {
            display: flex;
            gap: 15px;
            font-size: 14px;
        }
        .stat {
            color: #ff0;
        }
        .status {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status.on { background: #0f0; }
        .status.off { background: #f00; }
        .controls {
            display: flex;
            gap: 8px;
        }
        .btn {
            background: #222;
            color: #0f0;
            border: 1px solid #0f0;
            padding: 4px 12px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .btn:hover { background: #0f0; color: #000; }
        .log-container {
            height: calc(100vh - 40px);
            overflow-y: auto;
            padding: 5px 10px;
            font-size: 13px;
            line-height: 1.3;
        }
        .log {
            white-space: nowrap;
            color: #0f0;
        }
        .tag { color: #0ff; font-weight: bold; }
        .time { color: #ff0; }
        .serial { color: #f0f; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #000; }
        ::-webkit-scrollbar-thumb { background: #0f0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">RFID LIVE ULTRA</div>
        <div class="stats">
            <span class="stat"><span id="count">0</span> détections</span>
            <span class="stat"><span id="rate">0</span>/s</span>
            <span><span class="status off" id="status"></span><span id="statusText">Déconnecté</span></span>
        </div>
        <div class="controls">
            <button class="btn" id="pauseBtn" onclick="togglePause()">⏸ Pause</button>
            <button class="btn" onclick="clearAll()">🗑 Vider</button>
        </div>
    </div>
    <div class="log-container" id="logs"></div>

    <script>
        const MAX_LOGS = 100; // Display limit (server keeps 500 in cache)
        let logs = [];
        let count = 0;
        let paused = false;
        let lastId = 0;
        let rateCounter = 0;
        let lastRateUpdate = Date.now();
        let pollingInterval = null;

                function poll() {
            if (paused) return;

            console.log(`📡 Polling since ID: ${lastId}`);

            fetch(`/api/rfid/raw-logs?since=${lastId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('✅ Poll response:', data);

                    // Connection réussie
                    document.getElementById('status').className = 'status on';
                    document.getElementById('statusText').textContent = 'Connecté';

                    if (data.success && data.logs && data.logs.length > 0) {
                        console.log(`🔔 ${data.logs.length} new detections received`);

                        // Process new logs (newest first already from API)
                        data.logs.forEach(log => {
                            // Extract tag from the request body
                            let tags = []; // Collect all tags from the request

                                                        try {
                                // Parse the JSON string - handle truncated data
                                let body;
                                try {
                                    body = typeof log.data === 'string' ? JSON.parse(log.data) : log.data;
                                } catch (parseError) {
                                    // Data is truncated - try to salvage what we can
                                    console.warn('⚠️ Truncated JSON, attempting to salvage:', log.data.substring(0, 100));
                                    
                                    // Try to extract tags using regex from the partial JSON
                                    const matches = log.data.matchAll(/"serial":"?\[?(\d+)\]?"?/g);
                                    for (const match of matches) {
                                        if (match[1]) tags.push(match[1]);
                                    }
                                    
                                    // Skip the rest of parsing if we found tags
                                    if (tags.length > 0) {
                                        console.log(`🏷️  Salvaged ${tags.length} tags from truncated data`);
                                        throw new Error('SALVAGED'); // Skip to rendering
                                    }
                                    throw parseError; // Re-throw if we couldn't salvage
                                }

                                // Handle double encapsulation: {"data":{"serial":"[20000005]",...}}
                                if (body.data && typeof body.data === 'object') {
                                    body = body.data;
                                } else if (body.data && typeof body.data === 'string') {
                                    body = JSON.parse(body.data);
                                }

                                // Extract serial(s) and remove brackets []
                                const cleanSerial = (s) => s ? s.replace(/[\[\]]/g, '').trim() : null;

                                if (Array.isArray(body)) {
                                    // Body is array: [{"serial":"2000042","timestamp":...}]
                                    body.forEach(item => {
                                        if (item.serial) tags.push(cleanSerial(item.serial));
                                    });
                                } else if (body.serial) {
                                    // Body is object: {"serial":"[2000042]",...}
                                    const serial = cleanSerial(body.serial);
                                    // Check if serial contains multiple comma-separated tags
                                    if (serial && serial.includes(',')) {
                                        tags = serial.split(',').map(t => t.trim()).filter(t => t);
                                    } else if (serial) {
                                        tags.push(serial);
                                    }
                                } else if (body.tag) {
                                    tags.push(cleanSerial(body.tag));
                                }

                                console.log(`🏷️  Parsed tags: ${tags.join(', ')} from`, body);
                            } catch (e) {
                                if (e.message !== 'SALVAGED') {
                                    console.error('❌ Error parsing log data:', e, log.data ? log.data.substring(0, 200) : 'no  data');
                                }
                            }


                            // Extract time from server timestamp
                            const timestamp = new Date(log.timestamp);
                            const time = timestamp.toLocaleTimeString('fr-FR', {
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                fractionalSecondDigits: 3
                            });

                            // Create ONE entry per tag (instead of grouping all tags together)
                            if (tags.length > 0) {
                                tags.forEach(tag => {
                                    logs.unshift({ tag, time, serial: log.serial || 'N/A' });
                                    count++;
                                    rateCounter++;
                                });
                            } else {
                                // No tags found - still show the log entry
                                logs.unshift({ tag: 'N/A', time, serial: log.serial || 'N/A' });
                                count++;
                                rateCounter++;
                            }

                            // Update last ID
                            if (log.id > lastId) lastId = log.id;
                        });

                        // Strict limit
                        if (logs.length > MAX_LOGS) logs = logs.slice(0, MAX_LOGS);

                        // Update count display
                        document.getElementById('count').textContent = count;

                        // Render
                        console.log(`🎨 Rendering ${logs.length} logs in UI`);
                        render();
                    } else {
                        console.log('⏸ No new logs');
                    }
                })
                .catch(error => {
                    console.error('❌ Polling error:', error);
                    document.getElementById('status').className = 'status off';
                    document.getElementById('statusText').textContent = 'Déconnecté';
                });
        }


        function startPolling() {
            console.log('🔌 Starting polling (500ms interval)');
            document.getElementById('status').className = 'status on';
            document.getElementById('statusText').textContent = 'Connecté';

            // Poll every 500ms (faster than original 1s)
            pollingInterval = setInterval(poll, 500);

            // First poll immediately
            poll();
        }

        function render() {
            const container = document.getElementById('logs');
            container.innerHTML = logs.map(log =>
                `<div class="log"><span class="tag">[${log.tag}]</span> <span class="time">[${log.time}]</span> <span class="serial">[${log.serial}]</span></div>`
            ).join('');
        }

        function togglePause() {
            paused = !paused;
            const btn = document.getElementById('pauseBtn');
            btn.textContent = paused ? '▶ Reprendre' : '⏸ Pause';
        }

        function clearAll() {
            // Clear server cache
            fetch('/api/rfid/clear-logs', { method: 'POST' })
                .then(() => {
                    console.log('🗑️ Server cache cleared');
                })
                .catch(error => {
                    console.error('❌ Error clearing server cache:', error);
                });

            // Reset client state
            logs = [];
            count = 0;
            rateCounter = 0;
            lastId = 0; // IMPORTANT: Reset lastId to reload from start
            document.getElementById('count').textContent = '0';
            document.getElementById('rate').textContent = '0';
            render();
            console.log('🗑️ Client state cleared, lastId reset to 0');
        }

        // Update rate every second
        setInterval(() => {
            const now = Date.now();
            const elapsed = (now - lastRateUpdate) / 1000;
            const rate = Math.round(rateCounter / elapsed);
            document.getElementById('rate').textContent = rate;
            rateCounter = 0;
            lastRateUpdate = now;
        }, 1000);

        // Start polling on load
        startPolling();

        // Cleanup on unload
        window.addEventListener('beforeunload', () => {
            if (pollingInterval) clearInterval(pollingInterval);
        });
    </script>
</body>
</html>
