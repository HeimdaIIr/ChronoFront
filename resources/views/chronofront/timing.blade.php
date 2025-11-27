@extends('chronofront.layout')

@section('title', 'Chronométrage')

@section('styles')
<style>
/* Override layout styles for timing page */
.main-content {
    background: #1a1d2e !important;
    min-height: 100vh;
    margin-left: -30px;
    margin-right: -30px;
    margin-top: -30px;
    padding: 0 !important;
}

/* Full dark theme */
.timing-wrapper {
    background: #1a1d2e;
    color: #e4e4e7;
    min-height: 100vh;
}

/* Top Bar */
.timing-top-bar {
    height: 70px;
    background: #0f1117;
    border-bottom: 1px solid #2a2d3e;
    padding: 0 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.race-info-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.race-title-main {
    font-size: 1.5rem;
    font-weight: 600;
    color: #e4e4e7;
    margin: 0;
}

.race-status-badge {
    padding: 0.5rem 1rem;
    background: #22c55e;
    color: white;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
}

.race-status-badge.pending {
    background: #f59e0b;
}

.top-bar-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.sync-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #22c55e;
    font-weight: 500;
}

.sync-indicator i {
    font-size: 1.2rem;
}

/* Readers Status Bar */
.readers-status-bar {
    display: flex;
    gap: 1.5rem;
    padding: 1rem 2rem;
    background: #0f1117;
    border-bottom: 1px solid #2a2d3e;
}

.reader-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.reader-status span {
    color: #a1a1aa;
}

.reader-status strong {
    color: #22c55e;
    font-weight: 600;
}

.reader-status.warning strong {
    color: #f59e0b;
}

.reader-status.error strong {
    color: #ef4444;
}

/* Clock Section */
.timing-clock-section {
    padding: 3rem 2rem;
    text-align: center;
    background: #0f1117;
    border-bottom: 1px solid #2a2d3e;
}

.main-clock {
    font-size: 7rem;
    font-weight: 200;
    color: #e4e4e7;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.03em;
}

/* Content Grid */
.timing-content {
    display: grid;
    grid-template-columns: 1fr 450px;
    gap: 0;
    min-height: calc(100vh - 350px);
}

/* Left: Table Section */
.table-section {
    background: #1a1d2e;
    display: flex;
    flex-direction: column;
}

.filters-bar-timing {
    display: flex;
    gap: 1rem;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #2a2d3e;
}

.search-timing {
    flex: 1;
    position: relative;
}

.search-timing i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #71717a;
    font-size: 1.1rem;
}

.search-timing input {
    width: 100%;
    height: 48px;
    background: #0f1117;
    border: 2px solid #2a2d3e;
    border-radius: 10px;
    padding: 0 1rem 0 3rem;
    color: #e4e4e7;
    font-size: 1rem;
    transition: all 0.2s;
}

.search-timing input:focus {
    outline: none;
    border-color: #3b82f6;
}

.filter-select-timing {
    min-width: 150px;
    height: 48px;
    background: #0f1117;
    border: 2px solid #2a2d3e;
    border-radius: 10px;
    padding: 0 1rem;
    color: #e4e4e7;
    font-size: 0.95rem;
}

.filter-select-timing:focus {
    outline: none;
    border-color: #3b82f6;
}

.btn-filter-timing {
    height: 48px;
    padding: 0 2rem;
    background: #3b82f6;
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-filter-timing:hover {
    background: #2563eb;
}

/* Results Table */
.results-wrapper {
    flex: 1;
    overflow: auto;
    padding: 0;
}

.timing-table {
    width: 100%;
    border-collapse: collapse;
}

.timing-table thead {
    position: sticky;
    top: 0;
    background: #0f1117;
    z-index: 10;
}

.timing-table thead th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: #a1a1aa;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #2a2d3e;
}

.timing-table tbody tr {
    border-bottom: 1px solid #2a2d3e;
    cursor: pointer;
    transition: background 0.15s;
}

.timing-table tbody tr:hover {
    background: #252836;
}

.timing-table tbody tr.selected {
    background: #1e3a5f;
}

.timing-table tbody td {
    padding: 1.2rem 1.5rem;
    font-size: 0.95rem;
    color: #e4e4e7;
}

.timing-table tbody td strong {
    font-weight: 700;
}

.cat-badge {
    padding: 0.3rem 0.75rem;
    background: #2a2d3e;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
}

/* Right Panel */
.detail-panel-timing {
    background: #0f1117;
    border-left: 2px solid #2a2d3e;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.detail-header-timing {
    padding: 2rem;
    border-bottom: 1px solid #2a2d3e;
}

.bib-display {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.bib-label {
    font-size: 0.75rem;
    color: #a1a1aa;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.bib-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #e4e4e7;
}

.runner-name-display {
    font-size: 1.5rem;
    font-weight: 600;
    color: #e4e4e7;
    margin-top: 0.5rem;
}

.detail-body-timing {
    padding: 2rem;
    flex: 1;
}

/* Timeline */
.checkpoint-timeline {
    margin-bottom: 2.5rem;
}

.timeline-checkpoint {
    display: flex;
    gap: 1.5rem;
    padding: 1rem 0;
    position: relative;
}

.timeline-checkpoint:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 35px;
    bottom: -15px;
    width: 2px;
    background: #2a2d3e;
}

.timeline-dot {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}

.timeline-checkpoint.warning .timeline-dot {
    background: #f59e0b;
}

.timeline-checkpoint.missing .timeline-dot {
    background: #71717a;
}

.timeline-info {
    flex: 1;
}

.checkpoint-name {
    font-size: 0.95rem;
    color: #a1a1aa;
    margin-bottom: 0.25rem;
}

.checkpoint-time {
    font-size: 1.3rem;
    font-weight: 600;
    color: #e4e4e7;
    font-variant-numeric: tabular-nums;
}

.checkpoint-action {
    font-size: 0.9rem;
    color: #f59e0b;
    text-decoration: underline;
    cursor: pointer;
}

/* Quick Entry */
.quick-entry-section {
    background: #1a1d2e;
    border: 1px solid #2a2d3e;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.quick-entry-section h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #e4e4e7;
}

.form-group-timing {
    margin-bottom: 1.25rem;
}

.form-group-timing label {
    display: block;
    font-size: 0.75rem;
    color: #a1a1aa;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.input-timing {
    width: 100%;
    height: 50px;
    background: #0f1117;
    border: 2px solid #2a2d3e;
    border-radius: 10px;
    padding: 0 1rem;
    color: #e4e4e7;
    font-size: 1rem;
}

.input-timing:focus {
    outline: none;
    border-color: #3b82f6;
}

/* Buttons */
.btn-timing {
    width: 100%;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-timing-primary {
    background: #3b82f6;
    color: white;
}

.btn-timing-primary:hover:not(:disabled) {
    background: #2563eb;
}

.btn-timing-success {
    background: #22c55e;
    color: white;
}

.btn-timing-success:hover:not(:disabled) {
    background: #16a34a;
}

.btn-timing-secondary {
    background: #2a2d3e;
    color: #e4e4e7;
}

.btn-timing-secondary:hover:not(:disabled) {
    background: #3a3d4e;
}

.btn-timing:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.actions-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Empty State */
.empty-state-timing {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    color: #71717a;
}

.empty-state-timing i {
    font-size: 5rem;
    margin-bottom: 1.5rem;
}

.empty-state-timing p {
    font-size: 1.2rem;
}

/* Selection Panel */
.selection-panel-timing {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
}

.selection-card-timing {
    width: 100%;
    max-width: 600px;
    background: #0f1117;
    border: 2px solid #2a2d3e;
    border-radius: 16px;
    padding: 3rem;
}

.selection-card-timing h3 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 2rem;
    color: #e4e4e7;
}

/* Alert */
.alert-timing {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    max-width: 500px;
    padding: 1.25rem 1.5rem;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 1rem;
    z-index: 1000;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.alert-timing.success {
    background: #22c55e;
    color: white;
}

.alert-timing.error {
    background: #ef4444;
    color: white;
}

.alert-timing i {
    font-size: 1.5rem;
}

/* Loading */
.loading-timing {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
}

.spinner-timing {
    width: 60px;
    height: 60px;
    border: 4px solid #2a2d3e;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 1.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
@endsection

@section('content')
<div class="timing-wrapper" x-data="timingManager()">
    <!-- Top Bar -->
    <div class="timing-top-bar">
        <div class="race-info-header">
            <template x-for="race in filteredRaces" :key="race.id">
                <div x-show="race.id == selectedRace" style="display: flex; align-items: center; gap: 1rem;">
                    <h1 class="race-title-main" x-text="race.name"></h1>
                    <span class="race-status-badge" x-text="race.start_time ? 'Course en cours' : 'En attente'" :class="{ 'pending': !race.start_time }"></span>
                </div>
            </template>
            <div x-show="!selectedRace" style="display: flex; align-items: center; gap: 1rem;">
                <h1 class="race-title-main">Aucune épreuve sélectionnée</h1>
                <button class="race-status-badge" style="cursor: pointer; border: none;" @click="showSelection = true">Sélectionnez une épreuve</button>
            </div>
        </div>
        <div class="top-bar-right">
            <div class="sync-indicator">
                <i class="bi bi-check-circle-fill"></i>
                <span>Synchro OK</span>
            </div>
        </div>
    </div>

    <!-- Readers Status -->
    <div class="readers-status-bar" x-show="selectedRace">
        <div class="reader-status">
            <span>Départ:</span>
            <strong>OK</strong>
        </div>
        <div class="reader-status">
            <span>Inter 1:</span>
            <strong>OK</strong>
        </div>
        <div class="reader-status warning">
            <span>Inter 2:</span>
            <strong>Attent.</strong>
        </div>
        <div class="reader-status">
            <span>Inter 3:</span>
            <strong>OK</strong>
        </div>
        <div class="reader-status">
            <span>Arrivée:</span>
            <strong>OK</strong>
        </div>
    </div>

    <!-- Clock -->
    <div class="timing-clock-section" x-show="selectedRace">
        <div class="main-clock" x-text="currentTime"></div>
    </div>

    <!-- Selection Panel (when no race) -->
    <div class="selection-panel-timing" x-show="!selectedRace">
        <div class="selection-card-timing">
            <h3>Sélection Épreuve</h3>
            <div class="form-group-timing">
                <label>Événement</label>
                <select class="input-timing" x-model="selectedEvent" @change="onEventChange">
                    <option value="">Sélectionnez un événement</option>
                    <template x-for="event in events" :key="event.id">
                        <option :value="event.id" x-text="event.name"></option>
                    </template>
                </select>
            </div>
            <div class="form-group-timing">
                <label>Épreuve</label>
                <select class="input-timing" x-model="selectedRace" @change="onRaceChange">
                    <option value="">Sélectionnez une épreuve</option>
                    <template x-for="race in filteredRaces" :key="race.id">
                        <option :value="race.id" x-text="race.name"></option>
                    </template>
                </select>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="timing-content" x-show="selectedRace">
        <!-- Left: Table -->
        <div class="table-section">
            <!-- Filters -->
            <div class="filters-bar-timing">
                <div class="search-timing">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Rechercher dossard / nom" x-model="searchQuery" @input="filterResults">
                </div>
                <select class="filter-select-timing" x-model="categoryFilter" @change="filterResults">
                    <option value="">Catégorie</option>
                </select>
                <select class="filter-select-timing" x-model="sasFilter" @change="filterResults">
                    <option value="">SAS</option>
                </select>
                <button class="btn-filter-timing">
                    <i class="bi bi-funnel"></i>
                    Filtrer
                </button>
            </div>

            <!-- Results Table -->
            <div class="results-wrapper" x-show="!loading">
                <table class="timing-table">
                    <thead>
                        <tr>
                            <th>Dossard</th>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>SAS</th>
                            <th>Départ</th>
                            <th>Inter 1</th>
                            <th>Inter 2</th>
                            <th>Arrivée</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="result in displayedResults" :key="result.id">
                            <tr :class="{ 'selected': selectedResult?.id === result.id }" @click="selectResult(result)">
                                <td><strong x-text="result.entrant?.bib_number"></strong></td>
                                <td x-text="result.entrant?.firstname + ' ' + result.entrant?.lastname"></td>
                                <td><span class="cat-badge" x-text="result.entrant?.category?.name || '-'"></span></td>
                                <td x-text="result.wave?.wave_number || '-'"></td>
                                <td x-text="formatTimeShort(result.raw_time)"></td>
                                <td>-</td>
                                <td>-</td>
                                <td x-text="formatTimeShort(result.raw_time)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div x-show="displayedResults.length === 0" class="empty-state-timing">
                    <i class="bi bi-inbox"></i>
                    <p>Aucune détection</p>
                </div>
            </div>

            <!-- Loading -->
            <div class="loading-timing" x-show="loading">
                <div class="spinner-timing"></div>
                <p style="color: #71717a;">Chargement...</p>
            </div>
        </div>

        <!-- Right: Detail Panel -->
        <div class="detail-panel-timing" x-show="selectedResult">
            <div class="detail-header-timing">
                <div class="bib-display">
                    <span class="bib-label">Dossard</span>
                    <span class="bib-number" x-text="'#' + (selectedResult?.entrant?.bib_number || '')"></span>
                </div>
                <div class="runner-name-display" x-text="(selectedResult?.entrant?.firstname || '') + ' ' + (selectedResult?.entrant?.lastname || '')"></div>
            </div>

            <div class="detail-body-timing">
                <!-- Timeline -->
                <div class="checkpoint-timeline">
                    <div class="timeline-checkpoint">
                        <div class="timeline-dot"></div>
                        <div class="timeline-info">
                            <div class="checkpoint-name">Départ</div>
                            <div class="checkpoint-time" x-text="formatTimeShort(selectedResult?.raw_time)"></div>
                        </div>
                    </div>
                    <div class="timeline-checkpoint">
                        <div class="timeline-dot"></div>
                        <div class="timeline-info">
                            <div class="checkpoint-name">Inter 1</div>
                            <div class="checkpoint-time">09:02:13</div>
                        </div>
                    </div>
                    <div class="timeline-checkpoint warning">
                        <div class="timeline-dot"></div>
                        <div class="timeline-info">
                            <div class="checkpoint-name">Non détecté</div>
                            <div class="checkpoint-action">ajouter temps manuel</div>
                        </div>
                    </div>
                    <div class="timeline-checkpoint">
                        <div class="timeline-dot"></div>
                        <div class="timeline-info">
                            <div class="checkpoint-name">Arrivée</div>
                            <div class="checkpoint-time" x-text="formatTimeShort(selectedResult?.raw_time)"></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Entry -->
                <div class="quick-entry-section">
                    <h4>Saisie Rapide</h4>
                    <form @submit.prevent="addTime">
                        <div class="form-group-timing">
                            <label>Numéro de dossard</label>
                            <input type="text" class="input-timing" x-model="bibNumber" placeholder="Ex: 254" :disabled="saving">
                        </div>
                        <button type="submit" class="btn-timing btn-timing-primary" :disabled="!bibNumber || saving">
                            <i class="bi bi-stopwatch"></i>
                            <span x-show="!saving">Enregistrer temps</span>
                            <span x-show="saving">Enregistrement...</span>
                        </button>
                    </form>
                </div>

                <!-- Actions -->
                <div class="actions-group">
                    <button class="btn-timing btn-timing-secondary" @click="recalculatePositions" :disabled="recalculating">
                        <i class="bi bi-calculator"></i>
                        <span x-show="!recalculating">Recalculer positions</span>
                        <span x-show="recalculating">Calcul...</span>
                    </button>
                    <template x-for="race in filteredRaces" :key="race.id">
                        <button x-show="race.id == selectedRace && !race.start_time" class="btn-timing btn-timing-success" @click="topDepart(race)" :disabled="startingRace">
                            <i class="bi bi-flag-fill"></i>
                            <span x-show="!startingRace">TOP DÉPART</span>
                            <span x-show="startingRace">Démarrage...</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <div x-show="successMessage" class="alert-timing success">
        <i class="bi bi-check-circle-fill"></i>
        <span x-text="successMessage"></span>
    </div>
    <div x-show="errorMessage" class="alert-timing error">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span x-text="errorMessage"></span>
    </div>
</div>
@endsection

@section('scripts')
<script>
function timingManager() {
    return {
        events: [],
        races: [],
        filteredRaces: [],
        results: [],
        displayedResults: [],
        selectedEvent: '',
        selectedRace: '',
        selectedResult: null,
        bibNumber: '',
        searchQuery: '',
        categoryFilter: '',
        sasFilter: '',
        loading: false,
        saving: false,
        startingRace: false,
        recalculating: false,
        successMessage: null,
        errorMessage: null,
        currentTime: '00:00:00',
        clockInterval: null,
        autoRefreshInterval: null,

        init() {
            this.loadEvents();
            this.loadRaces();
            this.startClock();
        },

        startClock() {
            this.updateClock();
            this.clockInterval = setInterval(() => this.updateClock(), 1000);
        },

        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('fr-FR');
        },

        async loadEvents() {
            try {
                const response = await axios.get('/events');
                this.events = response.data;
            } catch (error) {
                console.error('Erreur', error);
            }
        },

        async loadRaces() {
            try {
                const response = await axios.get('/races');
                this.races = response.data;
            } catch (error) {
                console.error('Erreur', error);
            }
        },

        onEventChange() {
            this.filteredRaces = this.selectedEvent
                ? this.races.filter(r => r.event_id == this.selectedEvent)
                : this.races;
            this.selectedRace = '';
        },

        async onRaceChange() {
            if (this.selectedRace) {
                await this.loadResults();
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
            }
        },

        async loadResults() {
            if (!this.selectedRace) return;
            this.loading = true;
            try {
                const response = await axios.get(`/results/race/${this.selectedRace}`);
                this.results = response.data.sort((a, b) => new Date(b.raw_time) - new Date(a.raw_time));
                this.filterResults();
            } catch (error) {
                console.error('Erreur', error);
            } finally {
                this.loading = false;
            }
        },

        filterResults() {
            this.displayedResults = this.results.filter(result => {
                const matchesSearch = !this.searchQuery ||
                    result.entrant?.bib_number?.toString().includes(this.searchQuery) ||
                    (result.entrant?.firstname + ' ' + result.entrant?.lastname).toLowerCase().includes(this.searchQuery.toLowerCase());
                return matchesSearch;
            });
        },

        selectResult(result) {
            this.selectedResult = result;
        },

        async addTime() {
            if (!this.bibNumber) return;
            this.saving = true;
            try {
                await axios.post('/results/time', {
                    race_id: this.selectedRace,
                    bib_number: this.bibNumber,
                    is_manual: true
                });
                this.successMessage = `Temps enregistré pour ${this.bibNumber}`;
                this.bibNumber = '';
                await this.loadResults();
                setTimeout(() => this.successMessage = null, 3000);
            } catch (error) {
                this.errorMessage = 'Erreur';
                setTimeout(() => this.errorMessage = null, 3000);
            } finally {
                this.saving = false;
            }
        },

        async recalculatePositions() {
            this.recalculating = true;
            try {
                await axios.post(`/results/race/${this.selectedRace}/recalculate`);
                this.successMessage = 'Positions recalculées';
                await this.loadResults();
                setTimeout(() => this.successMessage = null, 3000);
            } catch (error) {
                this.errorMessage = 'Erreur';
                setTimeout(() => this.errorMessage = null, 3000);
            } finally {
                this.recalculating = false;
            }
        },

        async topDepart(race) {
            if (!confirm(`TOP DÉPART pour "${race.name}" ?`)) return;
            this.startingRace = true;
            try {
                await axios.post(`/races/${race.id}/start`);
                race.start_time = new Date().toISOString();
                this.successMessage = `TOP DÉPART donné`;
                await this.loadRaces();
                await this.onEventChange();
                setTimeout(() => this.successMessage = null, 3000);
            } catch (error) {
                this.errorMessage = 'Erreur';
                setTimeout(() => this.errorMessage = null, 3000);
            } finally {
                this.startingRace = false;
            }
        },

        startAutoRefresh() {
            this.stopAutoRefresh();
            this.autoRefreshInterval = setInterval(() => this.loadResults(), 5000);
        },

        stopAutoRefresh() {
            if (this.autoRefreshInterval) clearInterval(this.autoRefreshInterval);
        },

        formatTimeShort(datetime) {
            if (!datetime) return '-';
            return new Date(datetime).toLocaleTimeString('fr-FR');
        }
    }
}
</script>
@endsection
