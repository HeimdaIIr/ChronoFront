@extends('chronofront.timing-layout')

@section('title', 'Chronométrage')

@section('styles')
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #1a1d2e;
    color: #e4e4e7;
    overflow: hidden;
}

.chrono-container {
    display: flex;
    height: 100vh;
}

/* Sidebar */
.chrono-sidebar {
    width: 70px;
    background: #0f1117;
    border-right: 1px solid #2a2d3e;
    display: flex;
    flex-direction: column;
    padding: 1.5rem 0;
    gap: 1.5rem;
}

.sidebar-icon {
    width: 70px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #71717a;
    font-size: 1.5rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.sidebar-icon:hover {
    color: #e4e4e7;
    background: #1a1d2e;
}

.sidebar-icon.active {
    color: #22c55e;
    background: #1a1d2e;
}

/* Main */
.chrono-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Top Bar */
.chrono-topbar {
    height: 70px;
    background: #0f1117;
    border-bottom: 1px solid #2a2d3e;
    padding: 0 2.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.event-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-right: 1.5rem;
}

.event-status {
    padding: 0.5rem 1rem;
    background: #22c55e;
    color: white;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.sync-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #22c55e;
    font-weight: 500;
}

.icon-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: #71717a;
    font-size: 1.3rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s;
}

.icon-btn:hover {
    background: #2a2d3e;
    color: #e4e4e7;
}

/* Content */
.chrono-content {
    display: grid;
    grid-template-columns: 1fr 400px;
    height: calc(100vh - 70px);
}

/* Left Side */
.chrono-left {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Clock + Status */
.clock-status-section {
    background: #0f1117;
    border-bottom: 1px solid #2a2d3e;
}

.main-clock {
    text-align: center;
    padding: 2rem 0 1.5rem;
    font-size: 8rem;
    font-weight: 200;
    letter-spacing: -0.04em;
    font-variant-numeric: tabular-nums;
}

.readers-status {
    display: flex;
    justify-content: center;
    gap: 2rem;
    padding: 1rem 0 1.5rem;
}

.reader-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.reader-item span {
    color: #a1a1aa;
}

.reader-item strong {
    color: #22c55e;
}

.reader-item.warning strong {
    color: #f59e0b;
}

/* Filters */
.filters-bar {
    display: flex;
    gap: 1rem;
    padding: 1.5rem 2rem;
    background: #1a1d2e;
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
    height: 45px;
    background: #0f1117;
    border: 2px solid #2a2d3e;
    border-radius: 8px;
    padding: 0 1rem 0 3rem;
    color: #e4e4e7;
    font-size: 0.95rem;
}

.search-box input:focus {
    outline: none;
    border-color: #3b82f6;
}

.filter-select {
    min-width: 140px;
    height: 45px;
    background: #0f1117;
    border: 2px solid #2a2d3e;
    border-radius: 8px;
    padding: 0 1rem;
    color: #e4e4e7;
    font-size: 0.9rem;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: #3b82f6;
}

.btn-filter {
    height: 45px;
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

/* Table */
.table-wrapper {
    flex: 1;
    overflow: auto;
}

.chrono-table {
    width: 100%;
    border-collapse: collapse;
}

.chrono-table thead {
    position: sticky;
    top: 0;
    background: #0f1117;
    z-index: 10;
}

.chrono-table thead th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: #a1a1aa;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #2a2d3e;
}

.chrono-table tbody tr {
    border-bottom: 1px solid #2a2d3e;
    cursor: pointer;
    transition: background 0.1s;
}

.chrono-table tbody tr:hover {
    background: #252836;
}

.chrono-table tbody tr.selected {
    background: #1e3a5f;
}

.chrono-table tbody td {
    padding: 1.1rem 1.5rem;
    font-size: 0.9rem;
}

.chrono-table tbody td strong {
    font-weight: 700;
    font-size: 1rem;
}

.cat-tag {
    padding: 0.25rem 0.6rem;
    background: #2a2d3e;
    border-radius: 5px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Right Panel */
.chrono-right {
    background: #0f1117;
    border-left: 2px solid #2a2d3e;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.detail-header {
    padding: 2rem;
    border-bottom: 1px solid #2a2d3e;
}

.bib-title {
    font-size: 0.75rem;
    color: #a1a1aa;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.bib-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.runner-name {
    font-size: 1.4rem;
    font-weight: 600;
}

.detail-body {
    padding: 2rem;
}

/* Timeline */
.timeline {
    margin-bottom: 2.5rem;
}

.timeline-item {
    display: flex;
    gap: 1.5rem;
    padding: 1rem 0;
    position: relative;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 11px;
    top: 40px;
    bottom: -15px;
    width: 2px;
    background: #2a2d3e;
}

.timeline-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
    z-index: 1;
}

.timeline-item.warning .timeline-dot {
    background: #f59e0b;
}

.timeline-content {
    flex: 1;
}

.checkpoint-label {
    font-size: 0.9rem;
    color: #a1a1aa;
    margin-bottom: 0.25rem;
}

.checkpoint-time {
    font-size: 1.3rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.checkpoint-action {
    font-size: 0.9rem;
    color: #f59e0b;
    text-decoration: underline;
    cursor: pointer;
}

/* Add Manual Time */
.manual-entry {
    background: #1a1d2e;
    border: 1px solid #2a2d3e;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.manual-entry h4 {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.manual-entry input {
    width: 100%;
    height: 48px;
    background: #0f1117;
    border: 2px solid #2a2d3e;
    border-radius: 8px;
    padding: 0 1rem;
    color: #e4e4e7;
    font-size: 0.95rem;
    margin-bottom: 1rem;
}

.manual-entry input:focus {
    outline: none;
    border-color: #3b82f6;
}

.btn-manual {
    width: 100%;
    height: 48px;
    background: #3b82f6;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-manual:hover {
    background: #2563eb;
}

/* Alert */
.alert-bar {
    position: fixed;
    bottom: 0;
    left: 70px;
    right: 0;
    padding: 1.25rem 2rem;
    background: #92400e;
    border-top: 1px solid #b45309;
    z-index: 100;
}

.alert-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #fef3c7;
}

/* Toast */
.toast {
    position: fixed;
    top: 2rem;
    right: 2rem;
    padding: 1rem 1.5rem;
    background: #22c55e;
    color: white;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    z-index: 1000;
    animation: slideInRight 0.3s ease;
}

.toast.error {
    background: #ef4444;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Empty State */
.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #71717a;
    padding: 4rem 2rem;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
}

/* Loading */
.loading {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 3px solid #2a2d3e;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Top Depart Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
}

.modal-content {
    background: #1a1d2e;
    border: 2px solid #2a2d3e;
    border-radius: 16px;
    padding: 2rem;
    max-width: 500px;
    width: 90%;
}

.modal-content h3 {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
}

.race-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.race-btn {
    width: 100%;
    padding: 1rem 1.5rem;
    background: #22c55e;
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    text-align: left;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s;
}

.race-btn:hover:not(:disabled) {
    background: #16a34a;
}

.race-btn:disabled {
    background: #2a2d3e;
    color: #71717a;
    cursor: not-allowed;
}

.race-btn .time {
    font-size: 0.85rem;
    opacity: 0.9;
}

.btn-close-modal {
    width: 100%;
    padding: 0.75rem;
    background: #2a2d3e;
    border: none;
    border-radius: 8px;
    color: #e4e4e7;
    font-weight: 500;
    cursor: pointer;
}
</style>
@endsection

@section('content')
<div class="chrono-container" x-data="chronoApp()">
    <!-- Sidebar -->
    <div class="chrono-sidebar">
        <a href="{{ route('dashboard') }}" class="sidebar-icon" title="Dashboard"><i class="bi bi-house"></i></a>
        <a href="{{ route('events') }}" class="sidebar-icon" title="Événements"><i class="bi bi-calendar-event"></i></a>
        <a href="{{ route('races') }}" class="sidebar-icon" title="Épreuves"><i class="bi bi-trophy"></i></a>
        <a href="{{ route('entrants') }}" class="sidebar-icon" title="Participants"><i class="bi bi-people"></i></a>
        <a href="{{ route('waves') }}" class="sidebar-icon" title="Vagues"><i class="bi bi-list-ul"></i></a>
        <a href="{{ route('timing') }}" class="sidebar-icon active" title="Chronométrage"><i class="bi bi-stopwatch"></i></a>
        <a href="{{ route('results') }}" class="sidebar-icon" title="Résultats"><i class="bi bi-bar-chart"></i></a>
    </div>

    <!-- Main -->
    <div class="chrono-main">
        <!-- Top Bar -->
        <div class="chrono-topbar">
            <div style="display: flex; align-items: center;">
                <span class="event-title" x-text="eventName || 'ChronoFront'"></span>
                <span class="event-status" x-show="hasOngoingRaces()" style="background: #22c55e;">Course en cours</span>
                <span class="event-status" x-show="!hasOngoingRaces() && races.length > 0" style="background: #f59e0b;">En attente</span>
            </div>
            <div class="topbar-right">
                <div class="sync-status" x-show="readers.length > 0 && readers.every(r => r.is_online)">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Synchro OK</span>
                </div>
                <div class="sync-status" x-show="readers.length === 0 || readers.some(r => !r.is_online)" style="color: #f59e0b;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Lecteurs hors ligne</span>
                </div>
                <a href="{{ route('dashboard') }}" class="icon-btn"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>

        <!-- Content -->
        <div class="chrono-content">
            <!-- Left -->
            <div class="chrono-left">
                <!-- Clock + Readers Status -->
                <div class="clock-status-section">
                    <div class="main-clock" x-text="currentTime"></div>
                    <div class="readers-status" x-show="readers.length > 0">
                        <template x-for="reader in readers" :key="reader.id">
                            <div class="reader-item" :class="{ 'warning': !reader.is_online }">
                                <span x-text="reader.location || reader.name"></span>:
                                <strong x-text="reader.is_online ? 'OK' : 'Hors ligne'"></strong>
                            </div>
                        </template>
                    </div>
                    <div x-show="readers.length === 0" class="text-center py-3 text-muted" style="font-size: 0.9rem;">
                        Aucun lecteur configuré
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-bar">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" placeholder="Rechercher dossard / nom" x-model="searchQuery" @input="filterResults">
                    </div>
                    <select class="filter-select" x-model="categoryFilter" @change="filterResults">
                        <option value="">Catégorie</option>
                    </select>
                    <select class="filter-select" x-model="sasFilter" @change="filterResults">
                        <option value="">SAS</option>
                    </select>
                    <button class="btn-filter" @click="showTopDepartModal = true">
                        <i class="bi bi-flag-fill"></i>
                        TOP DÉPART
                    </button>
                </div>

                <!-- Table -->
                <div class="table-wrapper" x-show="!loading">
                    <table class="chrono-table">
                        <thead>
                            <tr>
                                <th>Dossard</th>
                                <th>Nom</th>
                                <th>Catégorie</th>
                                <th>SAS</th>
                                <th>Lecteur</th>
                                <th>Temps</th>
                                <th>Détection</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="result in displayedResults" :key="result.id">
                                <tr :class="{ 'selected': selectedResult?.id === result.id }" @click="selectResult(result)">
                                    <td><strong x-text="result.entrant?.bib_number"></strong></td>
                                    <td x-text="(result.entrant?.firstname || '') + ' ' + (result.entrant?.lastname || '')"></td>
                                    <td><span class="cat-tag" x-text="result.entrant?.category?.name || '-'"></span></td>
                                    <td x-text="result.wave?.wave_number || '-'"></td>
                                    <td x-text="result.reader_location || '-'"></td>
                                    <td><strong x-text="result.formatted_time || '-'"></strong></td>
                                    <td x-text="formatTime(result.raw_time)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div x-show="displayedResults.length === 0" class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>Aucune détection</p>
                    </div>
                </div>

                <div x-show="loading" class="loading">
                    <div class="spinner"></div>
                    <p style="color: #71717a;">Chargement...</p>
                </div>
            </div>

            <!-- Right -->
            <div class="chrono-right" x-show="selectedResult">
                <div class="detail-header">
                    <div class="bib-title">Dossard</div>
                    <div class="bib-value" x-text="'#' + (selectedResult?.entrant?.bib_number || '')"></div>
                    <div class="runner-name" x-text="(selectedResult?.entrant?.firstname || '') + ' ' + (selectedResult?.entrant?.lastname || '')"></div>
                </div>

                <div class="detail-body">
                    <!-- Info détails -->
                    <div class="mb-4">
                        <div class="row mb-2">
                            <div class="col-6" style="color: #a1a1aa; font-size: 0.85rem;">Épreuve:</div>
                            <div class="col-6" style="text-align: right;" x-text="selectedResult?.race?.name || '-'"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6" style="color: #a1a1aa; font-size: 0.85rem;">Vague:</div>
                            <div class="col-6" style="text-align: right;" x-text="selectedResult?.wave?.name || 'SAS ' + (selectedResult?.wave?.wave_number || '-')"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6" style="color: #a1a1aa; font-size: 0.85rem;">Lecteur:</div>
                            <div class="col-6" style="text-align: right;" x-text="selectedResult?.reader_location || '-'"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6" style="color: #a1a1aa; font-size: 0.85rem;">Détection:</div>
                            <div class="col-6" style="text-align: right;" x-text="formatTime(selectedResult?.raw_time)"></div>
                        </div>
                        <div class="row mb-2" style="border-top: 1px solid #2a2d3e; padding-top: 1rem;">
                            <div class="col-6" style="color: #22c55e; font-size: 0.85rem; font-weight: 600;">TEMPS TOTAL:</div>
                            <div class="col-6" style="text-align: right; font-size: 1.5rem; font-weight: 700; color: #22c55e;" x-text="selectedResult?.formatted_time || '-'"></div>
                        </div>
                        <div class="row" x-show="selectedResult?.speed">
                            <div class="col-6" style="color: #a1a1aa; font-size: 0.85rem;">Vitesse moyenne:</div>
                            <div class="col-6" style="text-align: right;" x-text="selectedResult?.speed ? selectedResult.speed + ' km/h' : '-'"></div>
                        </div>
                    </div>

                    <!-- Manual Entry -->
                    <div class="manual-entry">
                        <h4>Ajouter temps manuel</h4>
                        <form @submit.prevent="addManualTime">
                            <input type="text" placeholder="Numéro de dossard" x-model="manualBib" :disabled="saving">
                            <button type="submit" class="btn-manual" :disabled="!manualBib || saving">
                                <i class="bi bi-stopwatch"></i>
                                <span x-show="!saving">Enregistrer temps</span>
                                <span x-show="saving">Enregistrement...</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Bar - Only show if there are real issues -->
    <div class="alert-bar" x-show="alertMessage">
        <div class="alert-content">
            <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
            <span x-text="alertMessage"></span>
            <button @click="alertMessage = null" style="background: none; border: none; color: #fef3c7; margin-left: auto; cursor: pointer;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toastMessage" class="toast" :class="toastType">
        <i class="bi" :class="toastType === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'"></i>
        <span x-text="toastMessage"></span>
    </div>

    <!-- Top Depart Modal -->
    <div x-show="showTopDepartModal" class="modal-overlay" @click.self="showTopDepartModal = false">
        <div class="modal-content">
            <h3>Sélectionner le parcours</h3>
            <div class="race-list">
                <template x-for="race in races" :key="race.id">
                    <button class="race-btn" @click="topDepart(race)" :disabled="race.start_time || startingRace">
                        <span x-text="race.name"></span>
                        <span x-show="race.start_time" class="time" x-text="'Départ: ' + formatTime(race.start_time)"></span>
                        <span x-show="!race.start_time">Donner le TOP</span>
                    </button>
                </template>
            </div>
            <button class="btn-close-modal" @click="showTopDepartModal = false">Fermer</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function chronoApp() {
    return {
        eventName: '',
        currentEventId: null,
        currentTime: '00:00:00',
        races: [],
        readers: [],
        results: [],
        displayedResults: [],
        selectedResult: null,
        searchQuery: '',
        categoryFilter: '',
        sasFilter: '',
        loading: false,
        saving: false,
        startingRace: false,
        alertMessage: null,
        toastMessage: null,
        toastType: 'success',
        showTopDepartModal: false,
        manualBib: '',
        clockInterval: null,
        autoRefreshInterval: null,
        readerPingInterval: null,

        init() {
            this.startClock();
            this.loadEvent();
            this.loadRaces();
            this.loadReaders();
            this.loadAllResults();
            this.startAutoRefresh();
            this.startReaderPing();
        },

        startClock() {
            this.updateClock();
            this.clockInterval = setInterval(() => this.updateClock(), 1000);
        },

        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('fr-FR');
        },

        async loadEvent() {
            try {
                // Load first active event
                const response = await axios.get('/events');
                const activeEvent = response.data.find(e => e.is_active) || response.data[0];
                if (activeEvent) {
                    this.eventName = activeEvent.name;
                    this.currentEventId = activeEvent.id;
                    // Reload readers when event is loaded
                    this.loadReaders();
                }
            } catch (error) {
                console.error('Erreur chargement événement', error);
            }
        },

        async loadRaces() {
            try {
                const response = await axios.get('/races');
                this.races = response.data;
            } catch (error) {
                console.error('Erreur chargement courses', error);
            }
        },

        async loadReaders() {
            if (!this.currentEventId) {
                return; // Wait for event to load
            }

            try {
                // Load only readers for current event
                const response = await axios.get(`/readers/event/${this.currentEventId}`);
                this.readers = response.data;

                // Check if any reader has issues
                const offlineReaders = this.readers.filter(r => !r.is_online);
                if (offlineReaders.length > 0) {
                    this.alertMessage = `Attention : ${offlineReaders.length} lecteur(s) hors ligne`;
                } else if (this.readers.length > 0) {
                    this.alertMessage = null; // Clear alert if all readers are OK
                }
            } catch (error) {
                console.error('Erreur chargement lecteurs', error);
            }
        },

        startReaderPing() {
            // Ping readers every 10 seconds to check connection
            this.readerPingInterval = setInterval(() => this.loadReaders(), 10000);
        },

        async loadAllResults() {
            this.loading = true;
            try {
                const response = await axios.get('/results');
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

        hasOngoingRaces() {
            return this.races.some(r => r.start_time && !r.end_time);
        },

        selectResult(result) {
            this.selectedResult = result;
        },

        async addManualTime() {
            if (!this.manualBib) return;
            this.saving = true;
            try {
                await axios.post('/results/time', {
                    bib_number: this.manualBib,
                    is_manual: true
                });
                this.showToast(`Temps enregistré pour ${this.manualBib}`, 'success');
                this.manualBib = '';
                await this.loadAllResults();
            } catch (error) {
                this.showToast('Erreur', 'error');
            } finally {
                this.saving = false;
            }
        },

        async topDepart(race) {
            if (!confirm(`Donner le TOP DÉPART pour "${race.name}" ?`)) return;
            this.startingRace = true;
            try {
                await axios.post(`/races/${race.id}/start`);
                race.start_time = new Date().toISOString();
                this.showToast(`TOP DÉPART donné pour ${race.name}`, 'success');
                await this.loadRaces();
            } catch (error) {
                this.showToast('Erreur', 'error');
            } finally {
                this.startingRace = false;
                this.showTopDepartModal = false;
            }
        },

        startAutoRefresh() {
            this.autoRefreshInterval = setInterval(() => this.loadAllResults(), 5000);
        },

        showToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            setTimeout(() => this.toastMessage = null, 3000);
        },

        formatTime(datetime) {
            if (!datetime) return '-';
            return new Date(datetime).toLocaleTimeString('fr-FR');
        }
    }
}
</script>
@endsection
