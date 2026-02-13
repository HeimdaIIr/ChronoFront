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
            grid-template-columns: var(--grid-cols);
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
            grid-template-columns: var(--grid-cols);
            width: 100%;
            background: #0a0a0a;
            transition: all 0.2s;
            min-height: 0;
            overflow: hidden;
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
            white-space: nowrap;
        }

        /* Dynamic sizing based on line count */
        /* 5 LIGNES (XL) */
        .size-large .grid-header-cell {
            padding: 0.4rem 0.8rem;
            font-size: 1.6rem;
            line-height: 1.2;
        }

        .size-large .grid-row {
            flex: 0 0 calc(100% / 5);
            max-height: calc(100% / 5);
        }

        .size-large .grid-cell {
            padding: 0.3rem 0.8rem;
            font-size: 2.8rem;
            font-weight: 500;
            line-height: 1.1;
        }

        /* 10 LIGNES (M) */
        .size-medium .grid-header-cell {
            padding: 0.3rem 0.6rem;
            font-size: 1.2rem;
            line-height: 1.2;
        }

        .size-medium .grid-row {
            flex: 0 0 calc(100% / 10);
            max-height: calc(100% / 10);
        }

        .size-medium .grid-cell {
            padding: 0.25rem 0.6rem;
            font-size: 1.6rem;
            font-weight: 500;
            line-height: 1.1;
        }

        /* 20 LIGNES (S) */
        .size-small .grid-header-cell {
            padding: 0.2rem 0.4rem;
            font-size: 0.85rem;
            line-height: 1.2;
        }

        .size-small .grid-row {
            flex: 0 0 calc(100% / 20);
            max-height: calc(100% / 20);
        }

        .size-small .grid-cell {
            padding: 0.15rem 0.4rem;
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.1;
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
        }

        .col-club {
            color: #999;
            font-style: italic;
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

        .col-team {
            color: #E91E63;
            font-weight: 600;
        }

        .col-inter {
            color: #888;
            font-variant-numeric: tabular-nums;
        }

        .col-intermediate {
            color: #888;
            font-size: 0.9em;
            font-variant-numeric: tabular-nums;
        }

        /* Settings button */
        .settings-btn {
            background: none;
            border: 2px solid #FFD700;
            color: #FFD700;
            width: 38px;
            height: 38px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .settings-btn:hover {
            background: #2a2a2a;
        }

        /* Settings panel */
        .settings-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 200;
        }

        .settings-panel {
            position: fixed;
            top: 70px;
            right: 0;
            width: 300px;
            height: calc(100vh - 70px);
            background: #1a1a1a;
            border-left: 2px solid #FFD700;
            z-index: 201;
            padding: 1.2rem;
            overflow-y: auto;
            font-family: 'Roboto Condensed', sans-serif;
        }

        .settings-title {
            font-size: 1.1rem;
            color: #FFD700;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .settings-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 4px;
            margin-bottom: 0.2rem;
            transition: background 0.15s;
            font-size: 0.95rem;
        }

        .settings-item:hover {
            background: #2a2a2a;
        }

        .settings-item.active {
            color: #fff;
        }

        .settings-item.inactive {
            color: #555;
        }

        .settings-check {
            width: 16px;
            height: 16px;
            border: 2px solid #FFD700;
            border-radius: 3px;
            margin-right: 0.7rem;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }

        .settings-check.checked {
            background: #FFD700;
            color: #000;
        }

        .settings-separator {
            border-top: 1px solid #333;
            margin: 0.6rem 0;
        }

        .settings-arrows {
            margin-left: auto;
            display: flex;
            gap: 0.2rem;
        }

        .settings-arrow-btn {
            background: none;
            border: 1px solid #555;
            color: #888;
            width: 22px;
            height: 22px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .settings-arrow-btn:hover {
            border-color: #FFD700;
            color: #FFD700;
        }

        .settings-arrow-btn:disabled {
            opacity: 0.2;
            cursor: default;
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
                <button class="settings-btn" @click="showSettings = !showSettings" title="Colonnes affichees">&#9881;</button>
            </div>
        </div>

        <!-- Settings panel -->
        <template x-if="showSettings">
            <div>
                <div class="settings-overlay" @click="showSettings = false"></div>
                <div class="settings-panel">
                    <div class="settings-title">Colonnes affichees</div>
                    <template x-for="(colId, idx) in columnOrder" :key="colId">
                        <div class="settings-item" :class="visibleColumnIds.includes(colId) ? 'active' : 'inactive'">
                            <div class="settings-check" :class="visibleColumnIds.includes(colId) ? 'checked' : ''" @click="toggleColumn(colId)">
                                <span x-show="visibleColumnIds.includes(colId)">&#10003;</span>
                            </div>
                            <span x-text="getColumnLabel(colId)" @click="toggleColumn(colId)" style="cursor:pointer"></span>
                            <div class="settings-arrows" x-show="visibleColumnIds.includes(colId)">
                                <button type="button" class="settings-arrow-btn" @click.prevent.stop="moveColumn(colId, -1)" :disabled="isFirstVisible(colId)" title="Monter">&#9650;</button>
                                <button type="button" class="settings-arrow-btn" @click.prevent.stop="moveColumn(colId, 1)" :disabled="isLastVisible(colId)" title="Descendre">&#9660;</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Results -->
        <div class="results-container" :class="'size-' + lineSize" :style="'--grid-cols: ' + gridTemplateColumns">
            <div x-show="loading && results.length === 0" class="loading">
                Chargement...
            </div>

            <template x-if="results.length === 0 && !loading">
                <div class="no-data">
                    Aucun passage enregistre
                </div>
            </template>

            <template x-if="results.length > 0">
                <div class="results-grid">
                    <!-- Header -->
                    <div class="grid-header">
                        <template x-for="col in activeColumns" :key="col.id">
                            <div class="grid-header-cell"
                                 :style="'justify-content: ' + (col.align === 'center' ? 'center' : 'flex-start') + (col.align === 'left' ? '; padding-left: 0.5rem' : '')"
                                 x-text="col.label"></div>
                        </template>
                    </div>

                    <!-- Body -->
                    <div class="grid-body">
                        <template x-for="(result, index) in displayedResults" :key="result.id">
                            <div class="grid-row" :class="{ 'new-entry': result.is_new }">
                                <template x-for="col in activeColumns" :key="col.id">
                                    <div class="grid-cell" :class="col.cssClass" x-text="getCellValue(result, col)"></div>
                                </template>
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
                showSettings: false,

                // Column definitions (static columns)
                allColumns: [
                    { id: 'bib', label: 'Dossard', baseWidth: 6, cssClass: 'col-bib', align: 'center' },
                    { id: 'position', label: 'Pos', baseWidth: 5, cssClass: 'col-position', align: 'center' },
                    { id: 'category_pos', label: 'Pos/Cat', baseWidth: 7, cssClass: 'col-category-pos', align: 'center' },
                    { id: 'name', label: 'Nom et Prenom', baseWidth: 20, cssClass: 'col-name', align: 'left' },
                    { id: 'category', label: 'Cat.', baseWidth: 6, cssClass: 'col-category', align: 'center' },
                    { id: 'gender', label: 'Sexe', baseWidth: 4, cssClass: 'col-gender', align: 'center' },
                    { id: 'race', label: 'Parcours', baseWidth: 15, cssClass: 'col-race', align: 'left' },
                    { id: 'club', label: 'Club', baseWidth: 15, cssClass: 'col-club', align: 'left' },
                    { id: 'team', label: 'Equipe', baseWidth: 15, cssClass: 'col-team', align: 'left' },
                    { id: 'speed', label: 'Vitesse', baseWidth: 10, cssClass: 'col-speed', align: 'center' },
                    { id: 'time', label: 'Temps', baseWidth: 12, cssClass: 'col-time', align: 'center' },
                ],

                // Intermediate columns discovered from data
                discoveredIntermediates: [],

                // Master order of ALL columns (drives display order)
                columnOrder: ['bib', 'position', 'category_pos', 'name', 'category', 'gender', 'race', 'club', 'team', 'speed', 'time'],

                // Which columns are currently visible
                visibleColumnIds: ['bib', 'position', 'category_pos', 'name', 'category', 'gender', 'race', 'club', 'speed', 'time'],

                // Only visible columns, in configured order (for grid)
                get activeColumns() {
                    const all = [...this.allColumns, ...this.discoveredIntermediates];
                    return this.columnOrder
                        .filter(id => this.visibleColumnIds.includes(id))
                        .map(id => all.find(c => c.id === id))
                        .filter(Boolean);
                },

                get gridTemplateColumns() {
                    const cols = this.activeColumns;
                    if (cols.length === 0) return '1fr';
                    const total = cols.reduce((sum, c) => sum + c.baseWidth, 0);
                    return cols.map(c => (c.baseWidth / total * 100).toFixed(1) + '%').join(' ');
                },

                get displayedResults() {
                    const maxLines = {
                        'large': 5,
                        'medium': 10,
                        'small': 20
                    }[this.lineSize] || 10;
                    return this.results.slice(0, maxLines);
                },

                isFirstVisible(id) {
                    const visibleInOrder = this.columnOrder.filter(cid => this.visibleColumnIds.includes(cid));
                    return visibleInOrder[0] === id;
                },

                isLastVisible(id) {
                    const visibleInOrder = this.columnOrder.filter(cid => this.visibleColumnIds.includes(cid));
                    return visibleInOrder[visibleInOrder.length - 1] === id;
                },

                getColumnLabel(id) {
                    const all = [...this.allColumns, ...this.discoveredIntermediates];
                    const col = all.find(c => c.id === id);
                    return col ? col.label : id;
                },

                getCellValue(result, col) {
                    if (col.id.startsWith('inter_')) {
                        const order = parseInt(col.id.split('_')[1]);
                        const inter = result.intermediates?.find(i => i.order === order);
                        return inter ? inter.time : '-';
                    }
                    switch (col.id) {
                        case 'bib': return result.bib_number;
                        case 'position': return result.position || '-';
                        case 'category_pos': return result.category_position || '-';
                        case 'name': return result.firstname + ' ' + result.lastname;
                        case 'category': return result.category_name || '-';
                        case 'gender': return result.gender || '-';
                        case 'race': return result.race_name || '-';
                        case 'club': return result.club || '-';
                        case 'team': return result.team || '-';
                        case 'speed': return result.speed ? result.speed + ' km/h' : '-';
                        case 'time': return result.formatted_time || result.calculated_time_formatted || '-';
                        default: return '-';
                    }
                },

                toggleColumn(id) {
                    const idx = this.visibleColumnIds.indexOf(id);
                    if (idx >= 0) {
                        if (this.visibleColumnIds.length <= 1) return;
                        this.visibleColumnIds.splice(idx, 1);
                    } else {
                        this.visibleColumnIds.push(id);
                    }
                    this.saveSettings();
                },

                moveColumn(id, direction) {
                    const idx = this.columnOrder.indexOf(id);
                    if (idx < 0) return;

                    // Find the next visible neighbor in the given direction
                    let targetIdx = idx + direction;
                    while (targetIdx >= 0 && targetIdx < this.columnOrder.length) {
                        if (this.visibleColumnIds.includes(this.columnOrder[targetIdx])) {
                            break;
                        }
                        targetIdx += direction;
                    }
                    if (targetIdx < 0 || targetIdx >= this.columnOrder.length) return;

                    // Remove item from current position and insert at target
                    const arr = [...this.columnOrder];
                    arr.splice(idx, 1);
                    arr.splice(targetIdx > idx ? targetIdx : targetIdx, 0, id);
                    this.columnOrder = arr;
                    this.saveSettings();
                },

                saveSettings() {
                    localStorage.setItem('speaker_columns', JSON.stringify({
                        order: this.columnOrder,
                        visible: this.visibleColumnIds
                    }));
                },

                loadSettings() {
                    const saved = localStorage.getItem('speaker_columns');
                    if (saved) {
                        try {
                            const parsed = JSON.parse(saved);
                            if (parsed && parsed.order && Array.isArray(parsed.order)) {
                                this.columnOrder = parsed.order;
                                if (parsed.visible && Array.isArray(parsed.visible) && parsed.visible.length > 0) {
                                    this.visibleColumnIds = parsed.visible;
                                }
                            } else if (Array.isArray(parsed) && parsed.length > 0) {
                                // Legacy format migration
                                this.visibleColumnIds = parsed;
                            }
                        } catch (e) {}
                    }
                },

                init() {
                    this.loadSettings();
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
                        if (response.data) {
                            const events = Array.isArray(response.data) ? response.data : (response.data.data || []);
                            if (events.length > 0) {
                                const activeEvent = events.find(e => e.is_active === true || e.is_active === 1) || events[0];
                                this.eventName = activeEvent.name || 'ChronoFront Live';
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
                            team: r.entrant?.team || '',
                            calculated_time_formatted: this.formatSeconds(r.calculated_time),
                            intermediates: r.intermediates || [],
                            is_new: r.id > this.lastResultId
                        }));

                        if (newResults.length > 0) {
                            this.lastResultId = Math.max(...newResults.map(r => r.id));
                        }

                        // Discover intermediate checkpoints from data
                        const interMap = new Map();
                        newResults.forEach(r => {
                            (r.intermediates || []).forEach(inter => {
                                if (!interMap.has(inter.order)) {
                                    interMap.set(inter.order, inter.checkpoint);
                                }
                            });
                        });
                        const newIntermediates = [];
                        interMap.forEach((checkpoint, order) => {
                            const id = 'inter_' + order;
                            if (!this.discoveredIntermediates.find(c => c.id === id)) {
                                newIntermediates.push({
                                    id: id,
                                    label: checkpoint,
                                    baseWidth: 12,
                                    cssClass: 'col-inter',
                                    align: 'center'
                                });
                            }
                        });
                        if (newIntermediates.length > 0) {
                            this.discoveredIntermediates = [...this.discoveredIntermediates, ...newIntermediates];
                            // Add to columnOrder if not already present
                            newIntermediates.forEach(inter => {
                                if (!this.columnOrder.includes(inter.id)) {
                                    this.columnOrder.push(inter.id);
                                }
                            });
                        }

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
