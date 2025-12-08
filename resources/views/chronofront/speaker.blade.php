<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChronoFront - Écran Speaker</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Roboto+Condensed:wght@300;400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Bebas Neue', 'Roboto Condensed', 'Arial Narrow', Arial, sans-serif;
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
            height: 90px;
            flex-shrink: 0;
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
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            height: 100%;
        }

        .results-table thead {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .results-table tbody {
            display: block;
            overflow: hidden;
        }

        .results-table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .results-table thead th {
            background: #1a1a1a;
            color: #FFD700;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #FFD700;
        }

        .results-table tbody tr {
            background: #0a0a0a;
            transition: all 0.2s;
        }

        .results-table tbody tr:hover {
            background: #1a1a1a;
        }

        .results-table tbody tr.new-entry {
            animation: highlight 1s ease-in-out;
        }

        @keyframes highlight {
            0%, 100% { background: #0a0a0a; }
            50% { background: #2a4a2a; }
        }

        /* Dynamic sizing based on line count */
        /* 5 LIGNES (XL) */
        .size-large .results-table thead th {
            padding: 1rem;
            font-size: 2.5rem;
            line-height: 1.2;
        }

        .size-large .results-table tbody {
            height: calc(100vh - 90px - 4rem);
        }

        .size-large .results-table tbody tr {
            height: calc((100vh - 90px - 4rem) / 5);
        }

        .size-large .results-table tbody td {
            padding: 1rem;
            font-size: 2rem;
            font-weight: 500;
            vertical-align: middle;
            line-height: 1.2;
        }

        /* 10 LIGNES (M) */
        .size-medium .results-table thead th {
            padding: 0.8rem;
            font-size: 1.8rem;
            line-height: 1.2;
        }

        .size-medium .results-table tbody {
            height: calc(100vh - 90px - 3rem);
        }

        .size-medium .results-table tbody tr {
            height: calc((100vh - 90px - 3rem) / 10);
        }

        .size-medium .results-table tbody td {
            padding: 0.8rem;
            font-size: 1.4rem;
            font-weight: 500;
            vertical-align: middle;
            line-height: 1.2;
        }

        /* 20 LIGNES (S) */
        .size-small .results-table thead th {
            padding: 0.5rem;
            font-size: 1.2rem;
            line-height: 1.2;
        }

        .size-small .results-table tbody {
            height: calc(100vh - 90px - 2rem);
        }

        .size-small .results-table tbody tr {
            height: calc((100vh - 90px - 2rem) / 20);
        }

        .size-small .results-table tbody td {
            padding: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            vertical-align: middle;
            line-height: 1.2;
        }

        /* Column specific styles */
        .col-bib {
            color: #FFD700;
            font-weight: 700;
            font-size: 1.3em;
            text-align: center;
        }

        .col-position {
            color: #4CAF50;
            font-weight: 700;
            font-size: 1.2em;
            text-align: center;
        }

        .col-category-pos {
            color: #2196F3;
            font-weight: 600;
            text-align: center;
        }

        .col-name {
            color: #fff;
            font-weight: 600;
        }

        .col-category {
            color: #9C27B0;
            font-weight: 600;
            text-align: center;
        }

        .col-gender {
            color: #FF9800;
            font-weight: 600;
            text-align: center;
        }

        .col-race {
            color: #00BCD4;
            font-weight: 600;
        }

        .col-club {
            color: #999;
            font-style: italic;
        }

        .col-speed {
            color: #00D9FF;
            font-weight: 600;
            text-align: center;
            font-variant-numeric: tabular-nums;
        }

        .col-time {
            color: #FFD700;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            font-size: 1.3em;
            text-align: center;
        }

        .col-intermediate {
            color: #888;
            font-size: 0.9em;
            font-variant-numeric: tabular-nums;
        }

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
                            <th style="width: 5%">Dossard</th>
                            <th style="width: 4%">Pos</th>
                            <th style="width: 4%">Pos/Cat</th>
                            <th style="width: 20%">Nom et Prénom</th>
                            <th style="width: 6%">Cat.</th>
                            <th style="width: 3%">Sexe</th>
                            <th style="width: 10%">Parcours</th>
                            <th style="width: 15%">Club</th>
                            <th style="width: 8%">Vitesse</th>
                            <th x-show="hasIntermediates" style="width: 15%">Intermédiaires</th>
                            <th style="width: 10%">Temps</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(result, index) in displayedResults" :key="result.id">
                            <tr :class="{ 'new-entry': result.is_new }">
                                <td class="col-bib" style="width: 5%" x-text="result.bib_number"></td>
                                <td class="col-position" style="width: 4%" x-text="result.position || '-'"></td>
                                <td class="col-category-pos" style="width: 4%" x-text="result.category_position || '-'"></td>
                                <td class="col-name" style="width: 20%" x-text="result.firstname + ' ' + result.lastname"></td>
                                <td class="col-category" style="width: 6%" x-text="result.category_name || '-'"></td>
                                <td class="col-gender" style="width: 3%" x-text="result.gender || '-'"></td>
                                <td class="col-race" style="width: 10%" x-text="result.race_name || '-'"></td>
                                <td class="col-club" style="width: 15%" x-text="result.club || '-'"></td>
                                <td class="col-speed" style="width: 8%" x-text="result.speed ? result.speed.toFixed(2) + ' km/h' : '-'"></td>
                                <td x-show="hasIntermediates" class="col-intermediate" style="width: 15%">
                                    <template x-for="inter in result.intermediates" :key="inter.checkpoint">
                                        <span style="margin-right: 1rem;">
                                            <span x-text="inter.checkpoint"></span>: <span x-text="inter.time"></span>
                                        </span>
                                    </template>
                                </td>
                                <td class="col-time" style="width: 10%" x-text="result.formatted_time || result.calculated_time_formatted || '-'"></td>
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
                hasIntermediates: false,

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
                        console.log('Events response:', response.data);

                        if (response.data) {
                            // Handle both array and paginated response
                            const events = Array.isArray(response.data) ? response.data : (response.data.data || []);

                            if (events.length > 0) {
                                // Get first active event or just first event
                                const activeEvent = events.find(e => e.is_active === true || e.is_active === 1) || events[0];
                                this.eventName = activeEvent.name || 'ChronoFront Live';
                                console.log('Event name loaded:', this.eventName);
                            }
                        }
                    } catch (error) {
                        console.error('Error loading event:', error);
                        this.eventName = 'ChronoFront Live';
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
                            gender: r.entrant?.gender || '',
                            category_name: r.entrant?.category?.name || '',
                            category_position: r.category_position,
                            race_name: r.race?.name || '',
                            club: r.entrant?.club || '',
                            calculated_time_formatted: this.formatSeconds(r.calculated_time),
                            // Use intermediates from API (already formatted)
                            intermediates: r.intermediates || [],
                            is_new: r.id > this.lastResultId
                        }));

                        if (newResults.length > 0) {
                            this.lastResultId = Math.max(...newResults.map(r => r.id));
                        }

                        // Check if any result has intermediates
                        this.hasIntermediates = newResults.some(r => r.intermediates && r.intermediates.length > 0);

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
