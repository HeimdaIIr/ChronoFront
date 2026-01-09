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
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
            flex-shrink: 0;
        }

        .event-title {
            font-size: 1.6rem;
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
            font-size: 1.4rem;
            font-weight: 300;
            font-variant-numeric: tabular-nums;
            color: #fff;
        }

        .line-size-selector {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            color: #FFD700;
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            text-transform: uppercase;
        }

        .line-size-selector:hover {
            background: #2a2a2a;
        }

        /* Results container */
        .results-container {
            height: calc(100vh - 70px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Grid layout for results */
        .results-grid {
            display: grid;
            grid-template-columns: 6% 5% 7% 18% 6% 4% 15% 17% 10% 12%;
            width: 100%;
            height: 100%;
            overflow: hidden;
            grid-template-rows: auto 1fr;
        }

        /* Header row */
        .grid-header {
            display: contents;
        }

        .grid-header-cell {
            background: #1a1a1a;
            color: #FFD700;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #FFD700;
            display: flex;
            align-items: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Header alignment - match cell alignment */
        .grid-header-cell:nth-child(1),
        .grid-header-cell:nth-child(2),
        .grid-header-cell:nth-child(3),
        .grid-header-cell:nth-child(5),
        .grid-header-cell:nth-child(6),
        .grid-header-cell:nth-child(9),
        .grid-header-cell:nth-child(10) {
            justify-content: center;
        }

        .grid-header-cell:nth-child(4),
        .grid-header-cell:nth-child(7),
        .grid-header-cell:nth-child(8) {
            justify-content: flex-start;
            padding-left: 0.5rem;
        }

        /* Body container */
        .grid-body {
            grid-column: 1 / -1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Body rows */
        .grid-row {
            display: grid;
            grid-template-columns: 6% 5% 7% 18% 6% 4% 15% 17% 10% 12%;
            width: 100%;
            background: #0a0a0a;
            transition: all 0.2s;
        }

        .grid-row:hover {
            background: #1a1a1a;
        }

        .grid-row.new-entry {
            animation: highlight 1s ease-in-out;
        }

        @keyframes highlight {
            0%, 100% { background: #0a0a0a; }
            50% { background: #2a4a2a; }
        }

        .grid-cell {
            display: flex;
            align-items: center;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Dynamic sizing based on line count */
        /* 5 LIGNES (XL) */
        .size-large .grid-header-cell {
            padding: 0.4rem 0.8rem;
            font-size: 1.6rem;
            line-height: 1.2;
        }

        .size-large .grid-row {
            flex: 1;
        }

        .size-large .grid-cell {
            padding: 0.3rem 0.8rem;
            font-size: 1.4rem;
            font-weight: 500;
            line-height: 1.2;
        }

        /* 10 LIGNES (M) */
        .size-medium .grid-header-cell {
            padding: 0.3rem 0.6rem;
            font-size: 1.2rem;
            line-height: 1.2;
        }

        .size-medium .grid-row {
            flex: 1;
        }

        .size-medium .grid-cell {
            padding: 0.25rem 0.6rem;
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.2;
        }

        /* 20 LIGNES (S) */
        .size-small .grid-header-cell {
            padding: 0.2rem 0.4rem;
            font-size: 0.85rem;
            line-height: 1.2;
        }

        .size-small .grid-row {
            flex: 1;
        }

        .size-small .grid-cell {
            padding: 0.15rem 0.4rem;
            font-size: 0.75rem;
            font-weight: 500;
            line-height: 1.2;
        }

        /* Column specific styles */
        .col-bib {
            color: #FFD700;
            font-weight: 700;
            font-size: 1.15em;
            justify-content: center;
        }

        .col-position {
            color: #4CAF50;
            font-weight: 700;
            font-size: 1.1em;
            justify-content: center;
        }

        .col-category-pos {
            color: #2196F3;
            font-weight: 600;
            justify-content: center;
        }

        .col-name {
            color: #fff;
            font-weight: 600;
            white-space: normal;
            word-wrap: break-word;
            padding-left: 0.5rem;
        }

        .col-category {
            color: #9C27B0;
            font-weight: 600;
            justify-content: center;
        }

        .col-gender {
            color: #FF9800;
            font-weight: 600;
            justify-content: center;
        }

        .col-race {
            color: #00BCD4;
            font-weight: 600;
            white-space: normal;
            word-wrap: break-word;
            padding-left: 0.5rem;
        }

        .col-club {
            color: #999;
            font-style: italic;
            white-space: normal;
            word-wrap: break-word;
            padding-left: 0.5rem;
        }

        .col-speed {
            color: #00D9FF;
            font-weight: 600;
            justify-content: center;
            font-variant-numeric: tabular-nums;
        }

        .col-time {
            color: #FFD700;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            font-size: 1.15em;
            justify-content: center;
        }

        .col-intermediate {
            color: #888;
            font-size: 0.9em;
            font-variant-numeric: tabular-nums;
            white-space: normal;
            word-wrap: break-word;
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
                <div class="results-grid">
                    <!-- Header -->
                    <div class="grid-header">
                        <div class="grid-header-cell">Dossard</div>
                        <div class="grid-header-cell">Pos</div>
                        <div class="grid-header-cell">Pos/Cat</div>
                        <div class="grid-header-cell">Nom et Prénom</div>
                        <div class="grid-header-cell">Cat.</div>
                        <div class="grid-header-cell">Sexe</div>
                        <div class="grid-header-cell">Parcours</div>
                        <div class="grid-header-cell">Club</div>
                        <div class="grid-header-cell">Vitesse</div>
                        <div class="grid-header-cell">Temps</div>
                    </div>

                    <!-- Body -->
                    <div class="grid-body">
                        <template x-for="(result, index) in displayedResults" :key="result.id">
                            <div class="grid-row" :class="{ 'new-entry': result.is_new }">
                                <div class="grid-cell col-bib" x-text="result.bib_number"></div>
                                <div class="grid-cell col-position" x-text="result.position || '-'"></div>
                                <div class="grid-cell col-category-pos" x-text="result.category_position || '-'"></div>
                                <div class="grid-cell col-name" x-text="result.firstname + ' ' + result.lastname"></div>
                                <div class="grid-cell col-category" x-text="result.category_name || '-'"></div>
                                <div class="grid-cell col-gender" x-text="result.gender || '-'"></div>
                                <div class="grid-cell col-race" x-text="result.race_name || '-'"></div>
                                <div class="grid-cell col-club" x-text="result.club || '-'"></div>
                                <div class="grid-cell col-speed" x-text="result.speed ? result.speed + ' km/h' : '-'"></div>
                                <div class="grid-cell col-time" x-text="result.formatted_time || result.calculated_time_formatted || '-'"></div>
                            </div>
                        </template>
                    </div>
                </div>
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
                            const events = Array.isArray(response.data) ? response.data : (response.data.data || []);

                            if (events.length > 0) {
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
                            intermediates: r.intermediates || [],
                            is_new: r.id > this.lastResultId
                        }));

                        if (newResults.length > 0) {
                            this.lastResultId = Math.max(...newResults.map(r => r.id));
                        }

                        this.hasIntermediates = newResults.some(r => r.intermediates && r.intermediates.length > 0);
                        this.results = newResults;

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
                    setInterval(() => {
                        this.loadResults();
                    }, 2000);
                }
            }
        }
    </script>
</body>
</html>
