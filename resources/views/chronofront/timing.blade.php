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

.btn-manual-time {
    height: 45px;
    padding: 0 1.5rem;
    background: #f59e0b;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-manual-time.has-times {
    background: #dc2626;
    animation: pulse 2s infinite;
}

.btn-manual-time:hover {
    transform: scale(1.05);
}

.btn-import-csv {
    height: 45px;
    padding: 0 1.5rem;
    background: #10b981;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
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
                <div style="font-size: 1.1rem; font-variant-numeric: tabular-nums; color: #a1a1aa; margin-left: 1rem;" x-text="currentTime"></div>
                <a href="{{ route('dashboard') }}" class="icon-btn"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>

        <!-- Content -->
        <div class="chrono-content">
            <!-- Left -->
            <div class="chrono-left">
                <!-- Clock + Readers Status -->
                <div class="clock-status-section">
                    <!-- Race Selector -->
                    <div style="text-align: center; padding-top: 1.5rem; padding-bottom: 0.5rem;">
                        <select x-model="selectedRaceId" @change="switchRaceChrono()"
                                style="background: #1a1d2e; color: #e4e4e7; border: 1px solid #2a2d3e; border-radius: 8px; padding: 0.5rem 1rem; font-size: 0.95rem; cursor: pointer;">
                            <option value="">Sélectionner un parcours</option>
                            <template x-for="race in races" :key="race.id">
                                <option :value="race.id" x-text="race.name + (race.start_time ? ' (Démarré)' : '')"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Race Chrono Display -->
                    <div class="main-clock" x-text="raceChrono" x-show="selectedRaceId && getSelectedRace()?.start_time"></div>
                    <div class="main-clock" style="font-size: 3rem; color: #71717a;" x-show="!selectedRaceId || !getSelectedRace()?.start_time">
                        -- : -- : --
                    </div>
                    <div style="text-align: center; padding-bottom: 0.5rem; color: #a1a1aa; font-size: 0.9rem;" x-show="selectedRaceId && getSelectedRace()?.start_time">
                        <span>Départ: </span>
                        <span x-text="formatTime(getSelectedRace()?.start_time)"></span>
                    </div>

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
                        <option value="">Toutes catégories</option>
                        <template x-for="category in [...new Set(results.map(r => r.entrant?.category?.name).filter(c => c))]" :key="category">
                            <option :value="category" x-text="category"></option>
                        </template>
                    </select>
                    <select class="filter-select" x-model="sasFilter" @change="filterResults">
                        <option value="">Tous SAS</option>
                        <template x-for="wave in [...new Set(results.map(r => r.wave?.name).filter(w => w))]" :key="wave">
                            <option :value="wave" x-text="wave"></option>
                        </template>
                    </select>
                    <button class="btn-filter" @click="showTopDepartModal = true">
                        <i class="bi bi-flag-fill"></i>
                        TOP DÉPART
                    </button>
                    <button class="btn-manual-time" @click="addManualTimestamp" :class="{ 'has-times': manualTimestamps.length > 0 }">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span x-show="manualTimestamps.length === 0">TEMPS MANUEL</span>
                        <span x-show="manualTimestamps.length > 0" x-text="manualTimestamps.length"></span>
                    </button>
                    <button class="btn-import-csv" @click="showManualTimesModal = true" x-show="manualTimestamps.length > 0">
                        <i class="bi bi-file-earmark-arrow-up-fill"></i>
                        ATTRIBUER
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
                                <th>Parcours</th>
                                <th>SAS</th>
                                <th>Lecteur</th>
                                <th>Temps</th>
                                <th>Vit</th>
                                <th>Détection</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="result in displayedResults" :key="result.id">
                                <tr :class="{ 'selected': selectedResult?.id === result.id }" @click="selectResult(result)">
                                    <td><strong x-text="result.entrant?.bib_number"></strong></td>
                                    <td x-text="(result.entrant?.firstname || '') + ' ' + (result.entrant?.lastname || '')"></td>
                                    <td><span class="cat-tag" x-text="result.entrant?.category?.name || '-'"></span></td>
                                    <td x-text="result.race?.name || '-'"></td>
                                    <td x-text="result.wave?.name || '-'"></td>
                                    <td x-text="result.reader_location || '-'"></td>
                                    <td><strong x-text="result.formatted_time || '-'"></strong></td>
                                    <td x-text="result.speed ? result.speed + ' km/h' : '-'"></td>
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
                    <!-- Info de base -->
                    <div class="mb-3">
                        <div class="row mb-2">
                            <div class="col-6" style="color: #a1a1aa; font-size: 0.85rem;">Épreuve:</div>
                            <div class="col-6" style="text-align: right; font-size: 0.9rem;" x-text="selectedResult?.race?.name || '-'"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6" style="color: #a1a1aa; font-size: 0.85rem;">Catégorie:</div>
                            <div class="col-6" style="text-align: right;" x-text="selectedResult?.entrant?.category?.name || '-'"></div>
                        </div>
                    </div>

                    <!-- Checkpoint Timeline -->
                    <div class="mb-4" style="border-top: 1px solid #2a2d3e; padding-top: 1rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 1rem; color: #e4e4e7;">
                            <i class="bi bi-geo-alt-fill"></i> Checkpoints
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <template x-for="(checkpoint, index) in runnerCheckpoints" :key="index">
                                <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: #1a1d2e; border-radius: 8px; border-left: 3px solid"
                                     :style="`border-left-color: ${checkpoint.is_estimated ? '#f59e0b' : '#22c55e'}`">
                                    <div style="flex-shrink: 0; width: 8px; height: 8px; border-radius: 50%;"
                                         :style="`background: ${checkpoint.is_estimated ? '#f59e0b' : '#22c55e'}`"></div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; font-size: 0.9rem;" x-text="checkpoint.location"></div>
                                        <div style="font-size: 0.75rem; color: #a1a1aa;" x-text="checkpoint.distance + ' km'"></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 600; font-size: 0.95rem;"
                                             :style="`color: ${checkpoint.is_estimated ? '#f59e0b' : '#22c55e'}`"
                                             x-text="checkpoint.time_display"></div>
                                        <div style="font-size: 0.75rem; color: #a1a1aa;" x-show="checkpoint.is_estimated">Estimé</div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="runnerCheckpoints.length === 0" style="text-align: center; padding: 1rem; color: #71717a; font-size: 0.85rem;">
                                Aucun checkpoint configuré
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="mb-4" style="border-top: 1px solid #2a2d3e; padding-top: 1rem;">
                        <div class="row mb-2">
                            <div class="col-6" style="color: #22c55e; font-size: 0.85rem; font-weight: 600;">TEMPS TOTAL:</div>
                            <div class="col-6" style="text-align: right; font-size: 1.5rem; font-weight: 700; color: #22c55e;" x-text="selectedResult?.formatted_time || '-'"></div>
                        </div>
                        <div class="row" x-show="runnerAverageSpeed">
                            <div class="col-6" style="color: #a1a1aa; font-size: 0.85rem;">Vitesse moyenne:</div>
                            <div class="col-6" style="text-align: right;" x-text="runnerAverageSpeed ? runnerAverageSpeed.toFixed(2) + ' km/h' : '-'"></div>
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

    <!-- Manual Times Import Modal -->
    <div x-show="showManualTimesModal" class="modal-overlay" @click.self="showManualTimesModal = false">
        <div class="modal-content" style="max-width: 600px;">
            <h3>Attribution des temps manuels</h3>

            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h4 style="margin: 0; color: #dc2626;">
                        <i class="bi bi-clock-history"></i>
                        <span x-text="manualTimestamps.length"></span> temps enregistrés
                    </h4>
                    <button @click="clearManualTimestamps()" style="padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        <i class="bi bi-trash"></i> Tout supprimer
                    </button>
                </div>

                <div style="max-height: 200px; overflow-y: auto; background: #f9fafb; border-radius: 8px; padding: 1rem;">
                    <template x-for="(ts, index) in manualTimestamps" :key="index">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background: white; border-radius: 6px; margin-bottom: 0.5rem;">
                            <div>
                                <span style="font-weight: 600; margin-right: 1rem;" x-text="`#${index + 1}`"></span>
                                <span x-text="ts.time"></span>
                            </div>
                            <button @click="removeManualTimestamp(index)" style="padding: 0.25rem 0.5rem; background: #fee2e2; color: #dc2626; border: none; border-radius: 4px; cursor: pointer;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div style="background: #eff6ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 0.5rem 0; color: #1e40af;">
                    <i class="bi bi-info-circle-fill"></i> Format CSV
                </h4>
                <p style="margin: 0; color: #1e3a8a; font-size: 0.9rem;">
                    Le fichier CSV doit contenir <strong x-text="manualTimestamps.length"></strong> dossards (un par ligne), dans l'ordre des temps enregistrés.
                </p>
                <p style="margin: 0.5rem 0 0 0; color: #1e3a8a; font-size: 0.85rem; font-family: monospace;">
                    Exemple:<br>
                    422<br>
                    156<br>
                    89
                </p>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <i class="bi bi-file-earmark-arrow-up"></i> Sélectionner le fichier CSV
                </label>
                <input
                    type="file"
                    accept=".csv,.txt"
                    @change="csvFile = $event.target.files[0]"
                    style="width: 100%; padding: 0.75rem; border: 2px dashed #d1d5db; border-radius: 8px; cursor: pointer;"
                >
                <div x-show="csvFile" style="margin-top: 0.5rem; color: #059669;">
                    <i class="bi bi-check-circle-fill"></i>
                    <span x-text="csvFile?.name"></span>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button
                    @click="showManualTimesModal = false"
                    style="padding: 0.75rem 1.5rem; background: #e5e7eb; color: #374151; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;"
                >
                    Annuler
                </button>
                <button
                    @click="importManualTimesFromCSV()"
                    :disabled="!csvFile || importingManualTimes"
                    style="padding: 0.75rem 1.5rem; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;"
                    :style="!csvFile || importingManualTimes ? 'opacity: 0.5; cursor: not-allowed;' : ''"
                >
                    <i class="bi bi-check-circle-fill"></i>
                    <span x-show="!importingManualTimes">Importer</span>
                    <span x-show="importingManualTimes">Import en cours...</span>
                </button>
            </div>
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
        currentEvent: null,
        alertThreshold: 5,
        currentTime: '00:00:00',
        raceChrono: '00:00:00',
        selectedRaceId: null,
        races: [],
        readers: [],
        results: [],
        displayedResults: [],
        selectedResult: null,
        runnerCheckpoints: [],
        runnerAverageSpeed: null,
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
        showManualTimesModal: false,
        manualBib: '',
        manualTimestamps: [],
        csvFile: null,
        importingManualTimes: false,
        clockInterval: null,
        autoRefreshInterval: null,
        readerPingInterval: null,

        init() {
            this.startClock();
            this.loadEvent().then(() => {
                this.loadAlertThreshold();
                this.loadManualTimestampsFromStorage(); // Load manual timestamps after event is loaded
            });
            this.loadRaces().then(() => this.autoSelectLastStartedRace());
            this.loadReaders();
            this.loadAllResults();
            this.startAutoRefresh();
            this.startReaderPing();
            this.startAlertCheck();
        },

        startClock() {
            this.updateClock();
            this.clockInterval = setInterval(() => this.updateClock(), 1000);
        },

        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('fr-FR');
            this.updateRaceChrono();
        },

        updateRaceChrono() {
            const race = this.getSelectedRace();
            if (!race || !race.start_time) {
                this.raceChrono = '00:00:00';
                return;
            }

            const startTime = new Date(race.start_time);
            const now = new Date();
            const elapsed = Math.floor((now - startTime) / 1000); // seconds

            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;

            this.raceChrono = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },

        getSelectedRace() {
            return this.races.find(r => r.id == this.selectedRaceId);
        },

        switchRaceChrono() {
            this.updateRaceChrono();
        },

        autoSelectLastStartedRace() {
            // Auto-select the most recently started race
            const startedRaces = this.races.filter(r => r.start_time).sort((a, b) => new Date(b.start_time) - new Date(a.start_time));
            if (startedRaces.length > 0) {
                this.selectedRaceId = startedRaces[0].id;
                this.updateRaceChrono();
            }
        },

        async loadEvent() {
            try {
                // Load first active event
                const response = await axios.get('/events');
                const activeEvent = response.data.find(e => e.is_active) || response.data[0];
                if (activeEvent) {
                    this.currentEvent = activeEvent;
                    this.eventName = activeEvent.name;
                    this.currentEventId = activeEvent.id;
                    // Reload readers when event is loaded
                    this.loadReaders();
                }
            } catch (error) {
                console.error('Erreur chargement événement', error);
            }
        },

        loadAlertThreshold() {
            if (this.currentEvent && this.currentEvent.alert_threshold_minutes) {
                this.alertThreshold = this.currentEvent.alert_threshold_minutes;
            } else {
                this.alertThreshold = 5; // default
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

        async pingAllReaders() {
            if (!this.currentEventId) return;

            try {
                await axios.post(`/readers/event/${this.currentEventId}/ping-all`);
                // After ping, reload readers to get updated status
                await this.loadReaders();
            } catch (error) {
                console.error('Error pinging readers:', error);
            }
        },

        startReaderPing() {
            // Ping readers every 10 seconds to check connection
            this.pingAllReaders(); // Initial ping
            this.readerPingInterval = setInterval(() => this.pingAllReaders(), 10000);
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

        normalizeString(str) {
            if (!str) return '';
            // NFD normalization: decompose accented characters
            // Then remove diacritics (combining marks)
            return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        },

        filterResults() {
            this.displayedResults = this.results.filter(result => {
                // Filter by search query (bib number or name)
                if (this.searchQuery) {
                    const searchNormalized = this.normalizeString(this.searchQuery);
                    const bibNumber = result.entrant?.bib_number?.toString() || '';
                    const fullName = (result.entrant?.firstname || '') + ' ' + (result.entrant?.lastname || '');
                    const fullNameNormalized = this.normalizeString(fullName);

                    const matchesSearch = bibNumber.includes(this.searchQuery) ||
                        fullNameNormalized.includes(searchNormalized);

                    if (!matchesSearch) return false;
                }

                // Filter by category
                if (this.categoryFilter && result.entrant?.category?.name !== this.categoryFilter) {
                    return false;
                }

                // Filter by SAS (wave)
                if (this.sasFilter && result.wave?.name !== this.sasFilter) {
                    return false;
                }

                return true;
            });
        },

        hasOngoingRaces() {
            return this.races.some(r => r.start_time && !r.end_time);
        },

        selectResult(result) {
            this.selectedResult = result;
            this.calculateRunnerCheckpoints();
        },

        calculateRunnerCheckpoints() {
            if (!this.selectedResult || !this.selectedResult.entrant_id) {
                this.runnerCheckpoints = [];
                this.runnerAverageSpeed = null;
                return;
            }

            // Get all results for this runner
            const runnerResults = this.results.filter(r => r.entrant_id === this.selectedResult.entrant_id);

            // Get configured readers sorted by distance
            const sortedReaders = [...this.readers]
                .filter(r => r.distance_from_start !== undefined)
                .sort((a, b) => parseFloat(a.distance_from_start || 0) - parseFloat(b.distance_from_start || 0));

            if (sortedReaders.length === 0) {
                this.runnerCheckpoints = [];
                return;
            }

            // Build checkpoint list
            this.runnerCheckpoints = [];
            let lastRealCheckpoint = null;

            // Add race start as first checkpoint
            const race = this.getSelectedRace();
            if (race && race.start_time) {
                this.runnerCheckpoints.push({
                    location: 'DÉPART',
                    distance: 0,
                    time_display: this.formatTime(race.start_time),
                    raw_time: new Date(race.start_time),
                    is_estimated: false
                });
                lastRealCheckpoint = {
                    distance: 0,
                    raw_time: new Date(race.start_time)
                };
            }

            // Process each reader checkpoint
            for (let reader of sortedReaders) {
                // Find if runner was detected at this checkpoint
                const detection = runnerResults.find(r => r.reader_id === reader.id || r.reader_location === reader.location);

                if (detection && detection.raw_time) {
                    // Real detection
                    this.runnerCheckpoints.push({
                        location: reader.location,
                        distance: reader.distance_from_start,
                        time_display: this.formatTime(detection.raw_time),
                        raw_time: new Date(detection.raw_time),
                        is_estimated: false
                    });
                    lastRealCheckpoint = {
                        distance: parseFloat(reader.distance_from_start),
                        raw_time: new Date(detection.raw_time)
                    };
                } else if (lastRealCheckpoint) {
                    // Estimate time based on average speed
                    const estimatedTime = this.estimateCheckpointTime(lastRealCheckpoint, reader.distance_from_start);
                    if (estimatedTime) {
                        this.runnerCheckpoints.push({
                            location: reader.location,
                            distance: reader.distance_from_start,
                            time_display: this.formatTime(estimatedTime.toISOString()),
                            raw_time: estimatedTime,
                            is_estimated: true
                        });
                    }
                }
            }

            // Calculate average speed
            this.calculateAverageSpeed();
        },

        estimateCheckpointTime(lastCheckpoint, targetDistance) {
            // Find the next real checkpoint after lastCheckpoint to calculate average speed
            const nextRealIndex = this.runnerCheckpoints.findIndex(cp =>
                !cp.is_estimated && cp.distance > lastCheckpoint.distance
            );

            let averageSpeed = null;

            if (nextRealIndex > 0) {
                // Calculate speed between last two real checkpoints
                const nextReal = this.runnerCheckpoints[nextRealIndex];
                const distance = nextReal.distance - lastCheckpoint.distance; // km
                const timeMs = nextReal.raw_time - lastCheckpoint.raw_time;
                const timeHours = timeMs / (1000 * 60 * 60);
                averageSpeed = distance / timeHours; // km/h
            } else {
                // Use a default speed estimate if we don't have next checkpoint yet
                // Calculate from all available checkpoints
                averageSpeed = this.runnerAverageSpeed || 10; // default 10 km/h
            }

            if (!averageSpeed || averageSpeed <= 0) return null;

            // Calculate estimated time
            const distanceDiff = targetDistance - lastCheckpoint.distance;
            const timeNeededHours = distanceDiff / averageSpeed;
            const timeNeededMs = timeNeededHours * 60 * 60 * 1000;

            return new Date(lastCheckpoint.raw_time.getTime() + timeNeededMs);
        },

        calculateAverageSpeed() {
            // Calculate overall average speed from all real checkpoints
            const realCheckpoints = this.runnerCheckpoints.filter(cp => !cp.is_estimated);
            if (realCheckpoints.length < 2) {
                this.runnerAverageSpeed = null;
                return;
            }

            const first = realCheckpoints[0];
            const last = realCheckpoints[realCheckpoints.length - 1];
            const distance = last.distance - first.distance; // km
            const timeMs = last.raw_time - first.raw_time;
            const timeHours = timeMs / (1000 * 60 * 60);

            this.runnerAverageSpeed = distance / timeHours; // km/h
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
            // Auto-refresh every 3 seconds for real-time updates
            // Only refresh when there are started races
            this.autoRefreshInterval = setInterval(() => {
                if (this.hasOngoingRaces()) {
                    this.loadAllResults();
                }
            }, 3000);
        },

        startAlertCheck() {
            // Check for late runners every minute
            setInterval(() => this.checkForLateRunners(), 60000);
            // Run once immediately after 10 seconds
            setTimeout(() => this.checkForLateRunners(), 10000);
        },

        checkForLateRunners() {
            if (!this.hasOngoingRaces() || this.readers.length === 0) {
                return;
            }

            // Get all entrants from results who have at least one detection
            const entrantIds = [...new Set(this.results.map(r => r.entrant_id).filter(Boolean))];

            let lateRunners = [];

            for (let entrantId of entrantIds) {
                const runnerResults = this.results.filter(r => r.entrant_id === entrantId);
                if (runnerResults.length === 0) continue;

                // Get the entrant info from the first result
                const entrant = runnerResults[0].entrant;
                if (!entrant) continue;

                // Sort results by reader distance
                const sortedResults = runnerResults
                    .filter(r => r.reader_id)
                    .map(r => {
                        const reader = this.readers.find(rd => rd.id === r.reader_id);
                        return {
                            ...r,
                            distance: reader ? reader.distance_from_start : 0
                        };
                    })
                    .sort((a, b) => a.distance - b.distance);

                if (sortedResults.length < 2) continue;

                // Calculate average speed from last two checkpoints
                const last = sortedResults[sortedResults.length - 1];
                const secondLast = sortedResults[sortedResults.length - 2];

                const distance = last.distance - secondLast.distance;
                const timeMs = new Date(last.raw_time) - new Date(secondLast.raw_time);
                const timeHours = timeMs / (1000 * 60 * 60);
                const avgSpeed = distance / timeHours; // km/h

                if (avgSpeed <= 0) continue;

                // Find next checkpoint after last detection
                const nextCheckpoint = this.readers
                    .filter(r => r.distance_from_start > last.distance)
                    .sort((a, b) => a.distance_from_start - b.distance_from_start)[0];

                if (!nextCheckpoint) continue;

                // Calculate expected arrival time at next checkpoint
                const distanceToNext = nextCheckpoint.distance_from_start - last.distance;
                const timeNeededHours = distanceToNext / avgSpeed;
                const timeNeededMs = timeNeededHours * 60 * 60 * 1000;
                const expectedArrival = new Date(new Date(last.raw_time).getTime() + timeNeededMs);

                // Check if runner is late
                const now = new Date();
                const delayMinutes = (now - expectedArrival) / (1000 * 60);

                if (delayMinutes > this.alertThreshold) {
                    const severity = delayMinutes > 15 ? 'critical' : 'warning';
                    lateRunners.push({
                        entrant,
                        checkpoint: nextCheckpoint.location,
                        delayMinutes: Math.floor(delayMinutes),
                        severity
                    });
                }
            }

            // Update alert message
            if (lateRunners.length > 0) {
                const critical = lateRunners.filter(r => r.severity === 'critical');
                const warning = lateRunners.filter(r => r.severity === 'warning');

                let message = '';
                if (critical.length > 0) {
                    const runner = critical[0];
                    message = `🚨 CRITIQUE: Dossard #${runner.entrant.bib_number} (${runner.entrant.firstname} ${runner.entrant.lastname}) devrait être à ${runner.checkpoint} (retard ${runner.delayMinutes} min)`;
                } else if (warning.length > 0) {
                    const runner = warning[0];
                    message = `⚠️ Attention: Dossard #${runner.entrant.bib_number} (${runner.entrant.firstname} ${runner.entrant.lastname}) devrait être à ${runner.checkpoint} (retard ${runner.delayMinutes} min)`;
                }

                this.alertMessage = message;
            }
        },

        showToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            setTimeout(() => this.toastMessage = null, 3000);
        },

        formatTime(datetime) {
            if (!datetime) return '-';
            return new Date(datetime).toLocaleTimeString('fr-FR');
        },

        // Manual timing functions
        addManualTimestamp() {
            // Get current time in local timezone (not UTC)
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timestamp = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

            this.manualTimestamps.push({
                timestamp: timestamp,
                time: new Date().toLocaleTimeString('fr-FR')
            });
            this.saveManualTimestampsToStorage();
            this.showToast(`Temps ${this.manualTimestamps.length} enregistré`, 'success');
        },

        loadManualTimestampsFromStorage() {
            const stored = localStorage.getItem(`chronofront_manual_times_${this.currentEventId}`);
            if (stored) {
                this.manualTimestamps = JSON.parse(stored);
            }
        },

        saveManualTimestampsToStorage() {
            localStorage.setItem(`chronofront_manual_times_${this.currentEventId}`, JSON.stringify(this.manualTimestamps));
        },

        clearManualTimestamps() {
            if (!confirm(`Supprimer ${this.manualTimestamps.length} temps enregistrés ?`)) return;
            this.manualTimestamps = [];
            this.saveManualTimestampsToStorage();
            this.showManualTimesModal = false;
        },

        removeManualTimestamp(index) {
            this.manualTimestamps.splice(index, 1);
            this.saveManualTimestampsToStorage();
        },

        async importManualTimesFromCSV() {
            if (!this.csvFile) {
                alert('Veuillez sélectionner un fichier CSV');
                return;
            }

            this.importingManualTimes = true;

            try {
                const text = await this.csvFile.text();
                const lines = text.trim().split('\n').map(l => l.trim()).filter(l => l);

                if (lines.length !== this.manualTimestamps.length) {
                    alert(`Erreur : ${lines.length} dossards dans le CSV mais ${this.manualTimestamps.length} temps enregistrés`);
                    this.importingManualTimes = false;
                    return;
                }

                const times = this.manualTimestamps.map((t, index) => ({
                    timestamp: t.timestamp,
                    bib_number: lines[index]
                }));

                const response = await axios.post('/results/manual-batch', {
                    event_id: this.currentEventId,
                    times: times
                });

                this.showToast(`${response.data.created} temps ajoutés avec succès`, 'success');

                if (response.data.errors > 0) {
                    console.warn('Erreurs:', response.data.error_details);
                    alert(`Attention: ${response.data.errors} dossards non trouvés`);
                }

                this.manualTimestamps = [];
                this.csvFile = null;
                this.saveManualTimestampsToStorage();
                this.showManualTimesModal = false;
                await this.loadAllResults();

            } catch (error) {
                console.error('Erreur import:', error);
                alert('Erreur lors de l\'import: ' + (error.response?.data?.message || error.message));
            } finally {
                this.importingManualTimes = false;
            }
        }
    }
}
</script>
@endsection
