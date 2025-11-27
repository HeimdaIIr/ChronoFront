@extends('chronofront.layout')

@section('title', 'Chronométrage')

@section('content')
<div class="chrono-app" x-data="timingManager()">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-icons">
            <a href="/events" class="sidebar-icon" title="Événements">
                <i class="bi bi-calendar-event"></i>
            </a>
            <a href="/entrants" class="sidebar-icon" title="Participants">
                <i class="bi bi-people"></i>
            </a>
            <a href="/races" class="sidebar-icon" title="Épreuves">
                <i class="bi bi-trophy"></i>
            </a>
            <a href="/results" class="sidebar-icon active" title="Chronométrage">
                <i class="bi bi-stopwatch"></i>
            </a>
            <a href="#" class="sidebar-icon" title="Classements">
                <i class="bi bi-bar-chart"></i>
            </a>
            <a href="#" class="sidebar-icon" title="Lecteurs">
                <i class="bi bi-hdd-network"></i>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="race-info">
                <template x-for="race in filteredRaces" :key="race.id">
                    <div x-show="race.id == selectedRace">
                        <h1 class="race-title" x-text="race.name"></h1>
                        <span class="race-status" x-text="race.start_time ? 'Course en cours' : 'En attente'"></span>
                    </div>
                </template>
                <div x-show="!selectedRace">
                    <h1 class="race-title">Aucune épreuve sélectionnée</h1>
                    <span class="race-status">Sélectionnez une épreuve</span>
                </div>
            </div>
            <div class="top-bar-actions">
                <div class="sync-status">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Synchro OK</span>
                </div>
                <button class="icon-btn" @click="showSettings = !showSettings">
                    <i class="bi bi-gear"></i>
                </button>
            </div>
        </div>

        <!-- Readers Status -->
        <div class="readers-status" x-show="selectedRace">
            <div class="reader-badge status-ok">
                <span>Départ:</span>
                <strong>OK</strong>
            </div>
            <div class="reader-badge status-ok">
                <span>Inter 1:</span>
                <strong>OK</strong>
            </div>
            <div class="reader-badge status-warning">
                <span>Inter 2:</span>
                <strong>Attent.</strong>
            </div>
            <div class="reader-badge status-ok">
                <span>Inter 3:</span>
                <strong>OK</strong>
            </div>
            <div class="reader-badge status-ok">
                <span>Inter 4:</span>
                <strong>OK</strong>
            </div>
            <div class="reader-badge status-ok">
                <span>Arrivée:</span>
                <strong>OK</strong>
            </div>
        </div>

        <!-- Clock Section -->
        <div class="clock-section" x-show="selectedRace">
            <div class="race-clock" x-text="currentTime"></div>
        </div>

        <!-- Race Selection (when no race selected) -->
        <div class="race-selection-panel" x-show="!selectedRace">
            <div class="selection-card">
                <h3>Sélection Épreuve</h3>
                <div class="form-group">
                    <label>Événement</label>
                    <select class="form-control" x-model="selectedEvent" @change="onEventChange">
                        <option value="">Sélectionnez un événement</option>
                        <template x-for="event in events" :key="event.id">
                            <option :value="event.id" x-text="event.name"></option>
                        </template>
                    </select>
                </div>
                <div class="form-group">
                    <label>Épreuve</label>
                    <select class="form-control" x-model="selectedRace" @change="onRaceChange">
                        <option value="">Sélectionnez une épreuve</option>
                        <template x-for="race in filteredRaces" :key="race.id">
                            <option :value="race.id">
                                <span x-show="race.display_order" x-text="'#' + race.display_order + ' - '"></span>
                                <span x-text="race.name"></span>
                            </option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="filters-bar" x-show="selectedRace">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input
                    type="text"
                    placeholder="Rechercher dossard / nom"
                    x-model="searchQuery"
                    @input="filterResults"
                >
            </div>
            <select class="filter-select" x-model="categoryFilter" @change="filterResults">
                <option value="">Catégorie</option>
                <option value="V1F">V1F</option>
                <option value="V1E">V1E</option>
            </select>
            <select class="filter-select" x-model="sasFilter" @change="filterResults">
                <option value="">SAS</option>
                <option value="1">SAS 1</option>
                <option value="2">SAS 2</option>
            </select>
            <button class="filter-btn">
                <i class="bi bi-funnel"></i>
                Filtrer
            </button>
        </div>

        <!-- Results Table -->
        <div class="results-table-wrapper" x-show="selectedRace && !loading">
            <table class="results-table-dark">
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
                        <tr
                            class="result-row-dark"
                            :class="{ 'selected': selectedResult?.id === result.id }"
                            @click="selectResult(result)"
                        >
                            <td><strong x-text="result.entrant?.bib_number"></strong></td>
                            <td x-text="result.entrant?.firstname + ' ' + result.entrant?.lastname"></td>
                            <td><span class="category-badge" x-text="result.entrant?.category?.name"></span></td>
                            <td x-text="result.wave?.wave_number || '-'"></td>
                            <td x-text="formatTimeShort(result.raw_time)"></td>
                            <td>-</td>
                            <td>-</td>
                            <td x-text="formatTimeShort(result.raw_time)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Empty state -->
            <div x-show="results.length === 0" class="empty-state-dark">
                <i class="bi bi-inbox"></i>
                <p>Aucune détection pour cette épreuve</p>
            </div>
        </div>

        <!-- Loading state -->
        <div x-show="loading" class="loading-state-dark">
            <div class="spinner-dark"></div>
            <p>Chargement des résultats...</p>
        </div>

        <!-- Alert Bar -->
        <div class="alert-bar" x-show="errorMessage || successMessage">
            <div x-show="errorMessage" class="alert-message alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span x-text="errorMessage"></span>
            </div>
            <div x-show="successMessage" class="alert-message alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <span x-text="successMessage"></span>
            </div>
        </div>
    </div>

    <!-- Right Panel - Participant Detail -->
    <div class="detail-panel" x-show="selectedResult">
        <div class="detail-header">
            <div class="detail-bib">
                <span class="label">Dossard</span>
                <span class="value" x-text="'#' + (selectedResult?.entrant?.bib_number || '')"></span>
            </div>
            <button class="close-btn" @click="selectedResult = null">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="detail-body">
            <h2 class="runner-name" x-text="(selectedResult?.entrant?.firstname || '') + ' ' + (selectedResult?.entrant?.lastname || '')"></h2>

            <!-- Timeline -->
            <div class="timeline">
                <div class="timeline-item status-ok">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="checkpoint">Départ</span>
                        <span class="time" x-text="formatTimeShort(selectedResult?.raw_time)"></span>
                    </div>
                </div>

                <div class="timeline-item status-ok">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="checkpoint">Inter 1</span>
                        <span class="time">09:02:13</span>
                    </div>
                </div>

                <div class="timeline-item status-ok">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="checkpoint">Inter 2</span>
                        <span class="time">09:46:12</span>
                    </div>
                </div>

                <div class="timeline-item status-warning">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="checkpoint">Non détecté</span>
                        <span class="action">ajouter temps manuel</span>
                    </div>
                </div>

                <div class="timeline-item status-ok">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="checkpoint">Inter 4</span>
                        <span class="time">10:27:11</span>
                    </div>
                </div>

                <div class="timeline-item status-ok">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="checkpoint">Arrivée</span>
                        <span class="time">11:18:17</span>
                    </div>
                </div>
            </div>

            <!-- Quick Entry -->
            <div class="quick-entry-panel">
                <h4>Saisie Rapide</h4>
                <form @submit.prevent="addTime">
                    <div class="form-group">
                        <label>Numéro de dossard</label>
                        <input
                            type="text"
                            class="form-control"
                            x-model="bibNumber"
                            placeholder="Ex: 254"
                            :disabled="!selectedRace || saving"
                        >
                    </div>
                    <button type="submit" class="btn-primary" :disabled="!bibNumber || saving">
                        <i class="bi bi-stopwatch"></i>
                        <span x-show="!saving">Enregistrer temps</span>
                        <span x-show="saving">Enregistrement...</span>
                    </button>
                </form>
            </div>

            <!-- Actions -->
            <div class="detail-actions">
                <button class="btn-secondary" @click="recalculatePositions" :disabled="recalculating">
                    <i class="bi bi-calculator"></i>
                    <span x-show="!recalculating">Recalculer</span>
                    <span x-show="recalculating">Calcul...</span>
                </button>
                <template x-for="race in filteredRaces" :key="race.id">
                    <button
                        x-show="race.id == selectedRace && !race.start_time"
                        class="btn-success"
                        @click="topDepart(race)"
                        :disabled="startingRace"
                    >
                        <i class="bi bi-flag-fill"></i>
                        <span x-show="!startingRace">TOP DÉPART</span>
                        <span x-show="startingRace">Démarrage...</span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function timingManager() {
    return {
        events: [],
        races: [],
        filteredRaces: [],
        waves: [],
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
        autoRefreshInterval: null,
        currentTime: '00:00:00',
        clockInterval: null,

        init() {
            this.loadEvents();
            this.loadRaces();
            this.startClock();
        },

        startClock() {
            this.updateClock();
            this.clockInterval = setInterval(() => {
                this.updateClock();
            }, 1000);
        },

        updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            this.currentTime = `${hours}:${minutes}:${seconds}`;
        },

        async loadEvents() {
            try {
                const response = await axios.get('/events');
                this.events = response.data;
            } catch (error) {
                console.error('Erreur chargement événements', error);
            }
        },

        async loadRaces() {
            try {
                const response = await axios.get('/races');
                this.races = response.data;
            } catch (error) {
                console.error('Erreur chargement épreuves', error);
            }
        },

        onEventChange() {
            if (this.selectedEvent) {
                this.filteredRaces = this.races.filter(race => race.event_id == this.selectedEvent);
            } else {
                this.filteredRaces = this.races;
            }

            this.filteredRaces.sort((a, b) => {
                if (a.display_order && b.display_order) {
                    return a.display_order - b.display_order;
                }
                return 0;
            });

            this.selectedRace = '';
            this.results = [];
            this.displayedResults = [];
            this.selectedResult = null;
        },

        async onRaceChange() {
            if (this.selectedRace) {
                await this.loadWaves();
                await this.loadResults();
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
                this.results = [];
                this.displayedResults = [];
                this.selectedResult = null;
            }
        },

        async loadWaves() {
            try {
                const response = await axios.get(`/waves/race/${this.selectedRace}`);
                this.waves = response.data;
            } catch (error) {
                console.error('Erreur chargement vagues', error);
            }
        },

        async loadResults() {
            if (!this.selectedRace) return;

            this.loading = true;
            try {
                const response = await axios.get(`/results/race/${this.selectedRace}`);
                this.results = response.data.sort((a, b) => {
                    return new Date(b.raw_time) - new Date(a.raw_time);
                });
                this.filterResults();
            } catch (error) {
                console.error('Erreur chargement résultats', error);
            } finally {
                this.loading = false;
            }
        },

        filterResults() {
            this.displayedResults = this.results.filter(result => {
                const matchesSearch = !this.searchQuery ||
                    result.entrant?.bib_number?.toString().includes(this.searchQuery) ||
                    (result.entrant?.firstname + ' ' + result.entrant?.lastname).toLowerCase().includes(this.searchQuery.toLowerCase());

                const matchesCategory = !this.categoryFilter ||
                    result.entrant?.category?.name === this.categoryFilter;

                const matchesSas = !this.sasFilter ||
                    result.wave?.wave_number?.toString() === this.sasFilter;

                return matchesSearch && matchesCategory && matchesSas;
            });
        },

        selectResult(result) {
            this.selectedResult = result;
        },

        async addTime() {
            if (!this.bibNumber || !this.selectedRace) return;

            this.saving = true;
            this.errorMessage = null;

            try {
                await axios.post('/results/time', {
                    race_id: this.selectedRace,
                    bib_number: this.bibNumber,
                    is_manual: true
                });

                this.successMessage = `Temps enregistré pour le dossard ${this.bibNumber}`;
                this.bibNumber = '';
                await this.loadResults();

                setTimeout(() => {
                    this.successMessage = null;
                }, 3000);

            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Erreur enregistrement';
            } finally {
                this.saving = false;
            }
        },

        async recalculatePositions() {
            if (!this.selectedRace) return;

            this.recalculating = true;
            try {
                const response = await axios.post(`/results/race/${this.selectedRace}/recalculate`);
                this.successMessage = response.data.message;
                await this.loadResults();
            } catch (error) {
                this.errorMessage = 'Erreur recalcul';
            } finally {
                this.recalculating = false;
            }
        },

        async topDepart(race) {
            if (!confirm(`Donner le TOP DÉPART pour "${race.name}" ?`)) return;

            this.startingRace = true;
            try {
                await axios.post(`/races/${race.id}/start`);
                race.start_time = new Date().toISOString();
                this.successMessage = `TOP DÉPART donné pour "${race.name}"`;
                await this.loadRaces();
                await this.onEventChange();
            } catch (error) {
                this.errorMessage = 'Erreur TOP DÉPART';
            } finally {
                this.startingRace = false;
            }
        },

        startAutoRefresh() {
            this.stopAutoRefresh();
            this.autoRefreshInterval = setInterval(() => {
                this.loadResults();
            }, 5000);
        },

        stopAutoRefresh() {
            if (this.autoRefreshInterval) {
                clearInterval(this.autoRefreshInterval);
            }
        },

        formatTime(datetime) {
            if (!datetime) return 'N/A';
            return new Date(datetime).toLocaleTimeString('fr-FR');
        },

        formatTimeShort(datetime) {
            if (!datetime) return '-';
            const date = new Date(datetime);
            return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
    }
}
</script>

<style>
/* Base Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Dark Theme */
.chrono-app {
    display: flex;
    min-height: 100vh;
    background: #1a1d2e;
    color: #e4e4e7;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Sidebar */
.sidebar {
    width: 70px;
    background: #0f1117;
    border-right: 1px solid #2a2d3e;
    display: flex;
    flex-direction: column;
}

.sidebar-icons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem 0;
}

.sidebar-icon {
    width: 70px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #71717a;
    font-size: 1.5rem;
    text-decoration: none;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}

.sidebar-icon:hover {
    color: #e4e4e7;
    background: #1a1d2e;
}

.sidebar-icon.active {
    color: #22c55e;
    background: #1a1d2e;
    border-left-color: #22c55e;
}

/* Main Content */
.main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Top Bar */
.top-bar {
    height: 60px;
    background: #0f1117;
    border-bottom: 1px solid #2a2d3e;
    padding: 0 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.race-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.race-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #e4e4e7;
    margin-right: 1rem;
}

.race-status {
    padding: 0.25rem 0.75rem;
    background: #22c55e;
    color: white;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
}

.top-bar-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.sync-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #22c55e;
    font-weight: 500;
}

.icon-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: #71717a;
    font-size: 1.25rem;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s;
}

.icon-btn:hover {
    background: #2a2d3e;
    color: #e4e4e7;
}

/* Readers Status */
.readers-status {
    display: flex;
    gap: 1rem;
    padding: 1rem 2rem;
    border-bottom: 1px solid #2a2d3e;
}

.reader-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.reader-badge span {
    color: #a1a1aa;
}

.reader-badge strong {
    color: #22c55e;
}

.reader-badge.status-warning strong {
    color: #f59e0b;
}

.reader-badge.status-error strong {
    color: #ef4444;
}

/* Clock Section */
.clock-section {
    padding: 2rem;
    text-align: center;
    border-bottom: 1px solid #2a2d3e;
}

.race-clock {
    font-size: 6rem;
    font-weight: 300;
    letter-spacing: -0.02em;
    color: #e4e4e7;
    font-variant-numeric: tabular-nums;
}

/* Race Selection Panel */
.race-selection-panel {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.selection-card {
    width: 100%;
    max-width: 500px;
    background: #0f1117;
    border: 1px solid #2a2d3e;
    border-radius: 12px;
    padding: 2rem;
}

.selection-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    color: #e4e4e7;
}

/* Filters Bar */
.filters-bar {
    display: flex;
    gap: 1rem;
    padding: 1rem 2rem;
    border-bottom: 1px solid #2a2d3e;
}

.search-box {
    flex: 1;
    position: relative;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #71717a;
}

.search-box input {
    width: 100%;
    height: 40px;
    background: #0f1117;
    border: 1px solid #2a2d3e;
    border-radius: 8px;
    padding: 0 1rem 0 2.5rem;
    color: #e4e4e7;
    font-size: 0.875rem;
}

.search-box input:focus {
    outline: none;
    border-color: #3b82f6;
}

.filter-select,
.form-control {
    height: 40px;
    background: #0f1117;
    border: 1px solid #2a2d3e;
    border-radius: 8px;
    padding: 0 1rem;
    color: #e4e4e7;
    font-size: 0.875rem;
}

.filter-select:focus,
.form-control:focus {
    outline: none;
    border-color: #3b82f6;
}

.filter-btn {
    height: 40px;
    padding: 0 1.5rem;
    background: #3b82f6;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Results Table */
.results-table-wrapper {
    flex: 1;
    overflow: auto;
    padding: 1rem 2rem;
}

.results-table-dark {
    width: 100%;
    border-collapse: collapse;
}

.results-table-dark thead th {
    position: sticky;
    top: 0;
    background: #0f1117;
    padding: 0.75rem 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: #a1a1aa;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #2a2d3e;
}

.result-row-dark {
    border-bottom: 1px solid #2a2d3e;
    cursor: pointer;
    transition: background 0.1s;
}

.result-row-dark:hover {
    background: #252836;
}

.result-row-dark.selected {
    background: #1e3a5f;
}

.result-row-dark td {
    padding: 1rem;
    font-size: 0.875rem;
}

.category-badge {
    padding: 0.25rem 0.5rem;
    background: #2a2d3e;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Empty & Loading States */
.empty-state-dark,
.loading-state-dark {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #71717a;
    padding: 4rem;
}

.empty-state-dark i,
.loading-state-dark i {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.spinner-dark {
    width: 48px;
    height: 48px;
    border: 3px solid #2a2d3e;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Alert Bar */
.alert-bar {
    position: fixed;
    bottom: 0;
    left: 70px;
    right: 400px;
    padding: 1rem 2rem;
    background: #7c2d12;
    border-top: 1px solid #9a3412;
    z-index: 100;
}

.alert-message {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.alert-warning {
    color: #fed7aa;
}

.alert-success {
    color: #86efac;
}

/* Right Panel */
.detail-panel {
    width: 400px;
    background: #0f1117;
    border-left: 1px solid #2a2d3e;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.detail-header {
    padding: 1.5rem;
    border-bottom: 1px solid #2a2d3e;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.detail-bib {
    display: flex;
    flex-direction: column;
}

.detail-bib .label {
    font-size: 0.75rem;
    color: #a1a1aa;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.detail-bib .value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #e4e4e7;
}

.close-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: #71717a;
    cursor: pointer;
    border-radius: 6px;
}

.close-btn:hover {
    background: #2a2d3e;
    color: #e4e4e7;
}

.detail-body {
    padding: 1.5rem;
}

.runner-name {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 2rem;
    color: #e4e4e7;
}

/* Timeline */
.timeline {
    margin-bottom: 2rem;
}

.timeline-item {
    display: flex;
    gap: 1rem;
    padding: 0.75rem 0;
}

.timeline-item:not(:last-child) {
    border-left: 2px solid #2a2d3e;
    margin-left: 7px;
}

.timeline-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
    position: relative;
    left: -9px;
}

.timeline-item.status-warning .timeline-dot {
    background: #f59e0b;
}

.timeline-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
}

.timeline-content .checkpoint {
    font-size: 0.875rem;
    color: #a1a1aa;
}

.timeline-content .time {
    font-size: 1.125rem;
    font-weight: 600;
    color: #e4e4e7;
    font-variant-numeric: tabular-nums;
}

.timeline-content .action {
    font-size: 0.875rem;
    color: #f59e0b;
    text-decoration: underline;
    cursor: pointer;
}

/* Quick Entry Panel */
.quick-entry-panel {
    background: #1a1d2e;
    border: 1px solid #2a2d3e;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.quick-entry-panel h4 {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #e4e4e7;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-size: 0.75rem;
    color: #a1a1aa;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Buttons */
.btn-primary,
.btn-secondary,
.btn-success {
    width: 100%;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #2563eb;
}

.btn-secondary {
    background: #2a2d3e;
    color: #e4e4e7;
}

.btn-secondary:hover:not(:disabled) {
    background: #3a3d4e;
}

.btn-success {
    background: #22c55e;
    color: white;
}

.btn-success:hover:not(:disabled) {
    background: #16a34a;
}

.btn-primary:disabled,
.btn-secondary:disabled,
.btn-success:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.detail-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
</style>
@endsection
