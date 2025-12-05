<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChronoFront - Écran Speaker</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto Condensed', 'Arial Narrow', Arial, sans-serif;
            background: #000;
            color: #FFD700;
            overflow: hidden;
        }

        .speaker-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .speaker-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            border-bottom: 3px solid #FFD700;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .event-title {
            font-size: 2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .header-controls {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .clock {
            font-size: 1.8rem;
            font-weight: 300;
            font-variant-numeric: tabular-nums;
            color: #fff;
        }

        .line-size-selector {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            color: #FFD700;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            text-transform: uppercase;
        }

        .line-size-selector:hover {
            background: #2a2a2a;
        }

        /* Results table */
        .results-container {
            flex: 1;
            overflow: hidden;
            padding: 1rem;
        }

        .results-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        .results-table thead th {
            background: #1a1a1a;
            color: #FFD700;
            padding: 1rem;
            text-align: left;
            font-size: 1.2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #FFD700;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .results-table tbody tr {
            background: #0a0a0a;
            transition: all 0.2s;
        }

        .results-table tbody tr:hover {
            background: #1a1a1a;
            transform: scale(1.01);
        }

        .results-table tbody tr.new-entry {
            animation: highlight 1s ease-in-out;
        }

        @keyframes highlight {
            0%, 100% { background: #0a0a0a; }
            50% { background: #2a4a2a; }
        }

        .results-table tbody td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #222;
            font-weight: 500;
        }

        /* Size modifiers */
        .size-large .results-table tbody td {
            padding: 2.5rem 1.5rem;
            font-size: 2.5rem;
        }

        .size-large .results-table thead th {
            font-size: 1.8rem;
            padding: 1.5rem;
        }

        .size-medium .results-table tbody td {
            padding: 1.5rem 1rem;
            font-size: 1.8rem;
        }

        .size-medium .results-table thead th {
            font-size: 1.4rem;
            padding: 1.2rem 1rem;
        }

        .size-small .results-table tbody td {
            padding: 0.8rem 1rem;
            font-size: 1.2rem;
        }

        .size-small .results-table thead th {
            font-size: 1rem;
            padding: 0.8rem 1rem;
        }

        /* Column specific styles */
        .col-bib {
            color: #FFD700;
            font-weight: 700;
            font-size: 1.3em;
        }

        .col-name {
            color: #fff;
            font-weight: 600;
        }

        .col-position {
            color: #4CAF50;
            font-weight: 700;
            font-size: 1.2em;
        }

        .col-category-pos {
            color: #2196F3;
            font-weight: 600;
        }

        .col-club {
            color: #999;
            font-style: italic;
        }

        .col-time {
            color: #FFD700;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            font-size: 1.3em;
        }

        .col-intermediate {
            color: #888;
            font-size: 0.9em;
            font-variant-numeric: tabular-nums;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-v { background: #4CAF50; color: #000; }
        .status-dnf { background: #f44336; color: #fff; }
        .status-dsq { background: #ff9800; color: #000; }

        /* Loading indicator */
        .loading {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: #FFD700;
            color: #000;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 700;
            z-index: 100;
        }

        /* No data message */
        .no-data {
            text-align: center;
            padding: 4rem 2rem;
            font-size: 2rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="speaker-container" x-data="speakerScreen()" x-init="init()">
        <!-- Header -->
        <div class="speaker-header">
            <div class="event-title" x-text="eventName"></div>
            <div class="header-controls">
                <div class="clock" x-text="currentTime"></div>
                <select class="line-size-selector" x-model="lineSize">
                    <option value="large">5 Lignes (XL)</option>
                    <option value="medium">10 Lignes (M)</option>
                    <option value="small">20 Lignes (S)</option>
                </select>
            </div>
        </div>

        <!-- Results -->
        <div class="results-container" :class="'size-' + lineSize">
            <div x-show="loading && results.length === 0" class="loading">
                Chargement...
            </div>

            <template x-if="results.length === 0 && !loading">
                <div class="no-data">
                    Aucun passage enregistré
                </div>
            </template>

            <template x-if="results.length > 0">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th style="width: 8%">Dossard</th>
                            <th style="width: 20%">Nom</th>
                            <th style="width: 7%">Pos.</th>
                            <th style="width: 8%">Cat.</th>
                            <th style="width: 17%">Club</th>
                            <th style="width: 12%">Temps</th>
                            <th style="width: 28%">Intermédiaires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(result, index) in displayedResults" :key="result.id">
                            <tr :class="{ 'new-entry': result.is_new }">
                                <td class="col-bib" x-text="result.bib_number"></td>
                                <td class="col-name">
                                    <span x-text="result.firstname + ' ' + result.lastname"></span>
                                </td>
                                <td class="col-position" x-text="result.position || '-'"></td>
                                <td class="col-category-pos">
                                    <span x-text="result.category_name"></span>
                                    <span x-show="result.category_position" x-text="'(' + result.category_position + ')'"></span>
                                </td>
                                <td class="col-club" x-text="result.club || '-'"></td>
                                <td class="col-time" x-text="result.formatted_time || result.calculated_time_formatted || '-'"></td>
                                <td class="col-intermediate">
                                    <template x-for="inter in result.intermediates" :key="inter.checkpoint">
                                        <span style="margin-right: 1rem;">
                                            <span x-text="inter.checkpoint"></span>: <span x-text="inter.time"></span>
                                        </span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
        </div>
    </div>

    <script>
        function speakerScreen() {
            return {
                eventName: 'ChronoFront Live',
                currentTime: '00:00:00',
                results: [],
                loading: false,
                lineSize: 'medium',
                lastResultId: 0,

                get displayedResults() {
                    const maxLines = {
                        'large': 5,
                        'medium': 10,
                        'small': 20
                    }[this.lineSize] || 10;

                    return this.results.slice(0, maxLines);
                },

                init() {
                    this.loadEventInfo();
                    this.loadResults();
                    this.startClock();
                    this.startAutoRefresh();
                },

                startClock() {
                    setInterval(() => {
                        const now = new Date();
                        this.currentTime = now.toLocaleTimeString('fr-FR');
                    }, 1000);
                },

                async loadEventInfo() {
                    try {
                        const response = await axios.get('/api/events');
                        if (response.data && response.data.length > 0) {
                            // Get first active event or just first event
                            const activeEvent = response.data.find(e => e.is_active) || response.data[0];
                            this.eventName = activeEvent.name;
                        }
                    } catch (error) {
                        console.error('Error loading event:', error);
                    }
                },

                async loadResults() {
                    this.loading = true;
                    try {
                        const response = await axios.get('/api/results/live-feed');

                        // Mark new results
                        const newResults = response.data.map(r => ({
                            ...r,
                            bib_number: r.entrant?.bib_number || 'N/A',
                            firstname: r.entrant?.firstname || '',
                            lastname: r.entrant?.lastname || '',
                            category_name: r.entrant?.category?.name || '',
                            category_position: r.category_position,
                            club: r.entrant?.club || '',
                            calculated_time_formatted: this.formatSeconds(r.calculated_time),
                            intermediates: this.getIntermediateTimes(r),
                            is_new: r.id > this.lastResultId
                        }));

                        if (newResults.length > 0) {
                            this.lastResultId = Math.max(...newResults.map(r => r.id));
                        }

                        this.results = newResults;

                        // Remove 'new' class after animation
                        setTimeout(() => {
                            this.results = this.results.map(r => ({ ...r, is_new: false }));
                        }, 1000);

                    } catch (error) {
                        console.error('Error loading results:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                getIntermediateTimes(result) {
                    // TODO: Implement intermediate times logic
                    // For now, return empty array
                    return [];
                },

                formatSeconds(seconds) {
                    if (!seconds) return '-';
                    const h = Math.floor(seconds / 3600);
                    const m = Math.floor((seconds % 3600) / 60);
                    const s = seconds % 60;
                    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
                },

                startAutoRefresh() {
                    // Refresh every 2 seconds for ultra-fast updates
                    setInterval(() => {
                        this.loadResults();
                    }, 2000);
                }
            }
        }
    </script>
</body>
</html>
