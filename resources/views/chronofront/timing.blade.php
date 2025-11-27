@extends('chronofront.layout')

@section('title', 'Chronométrage')

@section('content')
<div class="timing-container" x-data="timingManager()">
    <!-- Header -->
    <div class="timing-header">
        <div class="header-content">
            <div class="header-title">
                <div class="pulse-dot"></div>
                <h1>Chronométrage Live</h1>
            </div>
            <p class="header-subtitle">Système de chronométrage temps réel haute précision</p>
        </div>
        <div class="header-actions">
            <button class="btn-recalculate" @click="recalculatePositions" :disabled="!selectedRace || recalculating">
                <i class="bi bi-calculator"></i>
                <span x-show="!recalculating">Recalculer</span>
                <span x-show="recalculating">
                    <span class="spinner"></span>
                    Calcul...
                </span>
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <div x-show="successMessage" x-transition class="alert alert-success-modern">
        <i class="bi bi-check-circle-fill"></i>
        <span x-text="successMessage"></span>
        <button @click="successMessage = null" class="alert-close">×</button>
    </div>

    <div x-show="errorMessage" x-transition class="alert alert-error-modern">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span x-text="errorMessage"></span>
        <button @click="errorMessage = null" class="alert-close">×</button>
    </div>

    <div class="timing-grid">
        <!-- Left Panel -->
        <div class="left-panel">
            <!-- Race Selection Card -->
            <div class="glass-card">
                <div class="card-header-modern gradient-primary">
                    <i class="bi bi-trophy"></i>
                    <h3>Sélection Épreuve</h3>
                </div>
                <div class="card-body-modern">
                    <div class="form-group-modern">
                        <label>Événement</label>
                        <select class="select-modern" x-model="selectedEvent" @change="onEventChange">
                            <option value="">Sélectionnez un événement</option>
                            <template x-for="event in events" :key="event.id">
                                <option :value="event.id" x-text="event.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="form-group-modern">
                        <label>Épreuve</label>
                        <select class="select-modern" x-model="selectedRace" @change="onRaceChange">
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

            <!-- TOP Départ Card -->
            <div x-show="selectedRace" x-transition class="glass-card start-card">
                <div class="card-header-modern gradient-success">
                    <i class="bi bi-flag-fill"></i>
                    <h3>Départ Épreuve</h3>
                </div>
                <div class="card-body-modern text-center">
                    <template x-for="race in filteredRaces" :key="race.id">
                        <div x-show="race.id == selectedRace">
                            <div class="race-info-badge">
                                <span x-show="race.display_order" class="order-number" x-text="'#' + race.display_order"></span>
                                <h4 x-text="race.name"></h4>
                            </div>
                            <div x-show="race.start_time" class="start-time-info">
                                <i class="bi bi-clock-history"></i>
                                Départ: <strong x-text="formatTime(race.start_time)"></strong>
                            </div>
                            <button
                                class="btn-start-race"
                                :class="race.start_time ? 'started' : ''"
                                @click="topDepart(race)"
                                :disabled="race.start_time || startingRace"
                            >
                                <span class="btn-icon">🚀</span>
                                <span class="btn-text" x-show="!race.start_time && !startingRace">TOP DÉPART</span>
                                <span class="btn-text" x-show="race.start_time">Départ donné</span>
                                <span class="btn-text" x-show="startingRace">
                                    <span class="spinner-sm"></span>
                                    Enregistrement...
                                </span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Active Waves -->
            <div x-show="selectedRace && waves.length > 0" x-transition class="glass-card">
                <div class="card-header-modern gradient-info">
                    <i class="bi bi-flag-fill"></i>
                    <h3>Vagues Actives</h3>
                </div>
                <div class="waves-list">
                    <template x-for="wave in waves" :key="wave.id">
                        <div class="wave-item">
                            <div class="wave-info">
                                <strong x-text="wave.name"></strong>
                                <span x-show="wave.is_started && !wave.end_time" class="wave-badge active">
                                    <i class="bi bi-play-fill"></i> En cours
                                </span>
                                <span x-show="!wave.is_started" class="wave-badge pending">
                                    <i class="bi bi-clock"></i> En attente
                                </span>
                                <div class="wave-time" x-show="wave.start_time">
                                    <i class="bi bi-clock"></i> <span x-text="formatTime(wave.start_time)"></span>
                                </div>
                            </div>
                            <span class="wave-count" x-text="(wave.entrants?.length || 0)"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Quick Entry Card -->
            <div x-show="selectedRace" x-transition class="glass-card quick-entry-card">
                <div class="card-header-modern gradient-warning">
                    <i class="bi bi-lightning-fill"></i>
                    <h3>Saisie Rapide</h3>
                </div>
                <div class="card-body-modern">
                    <form @submit.prevent="addTime">
                        <div class="quick-entry-input">
                            <label>N° Dossard</label>
                            <input
                                type="text"
                                class="input-modern input-large"
                                x-model="bibNumber"
                                placeholder="Ex: 2113"
                                autofocus
                                :disabled="!selectedRace || saving"
                            >
                        </div>
                        <button
                            type="submit"
                            class="btn-submit-time"
                            :disabled="!bibNumber || !selectedRace || saving"
                        >
                            <i class="bi bi-stopwatch"></i>
                            <span x-show="!saving">Enregistrer Temps</span>
                            <span x-show="saving">
                                <span class="spinner-sm"></span>
                                Enregistrement...
                            </span>
                        </button>
                        <p class="input-hint">Saisissez le dossard et appuyez sur Entrée</p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Panel - Results -->
        <div class="right-panel">
            <div class="glass-card results-card">
                <div class="card-header-modern gradient-dark">
                    <div class="header-left">
                        <i class="bi bi-list-check"></i>
                        <h3>Détections Temps Réel</h3>
                        <span x-show="results.length > 0" class="count-badge" x-text="results.length"></span>
                    </div>
                    <button class="btn-refresh" @click="loadResults" :disabled="!selectedRace">
                        <i class="bi bi-arrow-clockwise"></i> Actualiser
                    </button>
                </div>

                <div class="results-body">
                    <!-- Empty State -->
                    <div x-show="!selectedRace" class="empty-state">
                        <i class="bi bi-info-circle"></i>
                        <p>Sélectionnez une épreuve pour commencer</p>
                    </div>

                    <!-- Loading State -->
                    <div x-show="selectedRace && loading" class="loading-state">
                        <div class="spinner-large"></div>
                        <p>Chargement des résultats...</p>
                    </div>

                    <!-- No Results -->
                    <div x-show="selectedRace && !loading && results.length === 0" class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>Aucune détection enregistrée</p>
                    </div>

                    <!-- Results Table -->
                    <div x-show="selectedRace && !loading && results.length > 0" class="results-table-container">
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Heure</th>
                                    <th>Dossard</th>
                                    <th>Participant</th>
                                    <th>Vague</th>
                                    <th>Tour</th>
                                    <th>Temps</th>
                                    <th>Vitesse</th>
                                    <th>Statut</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="result in results" :key="result.id">
                                    <tr class="result-row">
                                        <td>
                                            <span class="time-cell" x-text="formatTime(result.raw_time)"></span>
                                            <span x-show="result.is_manual" class="manual-badge" title="Saisie manuelle">
                                                <i class="bi bi-pencil-fill"></i>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="bib-number" x-text="result.entrant?.bib_number"></span>
                                        </td>
                                        <td>
                                            <span class="participant-name" x-text="result.entrant?.firstname + ' ' + result.entrant?.lastname"></span>
                                        </td>
                                        <td>
                                            <span class="wave-tag" x-text="result.wave?.name"></span>
                                        </td>
                                        <td>
                                            <span class="lap-tag" x-text="'Tour ' + result.lap_number"></span>
                                        </td>
                                        <td>
                                            <span class="duration-cell" x-text="formatDuration(result.calculated_time)"></span>
                                        </td>
                                        <td>
                                            <span class="speed-cell" x-text="result.speed ? result.speed + ' km/h' : 'N/A'"></span>
                                        </td>
                                        <td>
                                            <select
                                                class="status-select"
                                                :class="'status-' + result.status.toLowerCase()"
                                                :value="result.status"
                                                @change="updateStatus(result, $event.target.value)"
                                            >
                                                <option value="V">V - Validé</option>
                                                <option value="DNS">DNS - Non parti</option>
                                                <option value="DNF">DNF - Abandon</option>
                                                <option value="DSQ">DSQ - Disqualifié</option>
                                                <option value="NS">NS - Non classé</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button
                                                class="btn-delete"
                                                @click="deleteResult(result)"
                                                title="Supprimer"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
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
        selectedEvent: '',
        selectedRace: '',
        bibNumber: '',
        loading: false,
        saving: false,
        startingRace: false,
        recalculating: false,
        successMessage: null,
        errorMessage: null,
        autoRefreshInterval: null,

        init() {
            this.loadEvents();
            this.loadRaces();
        },

        async loadEvents() {
            try {
                const response = await axios.get('/events');
                this.events = response.data;
            } catch (error) {
                console.error('Erreur lors du chargement des événements', error);
            }
        },

        async loadRaces() {
            try {
                const response = await axios.get('/races');
                this.races = response.data;
            } catch (error) {
                console.error('Erreur lors du chargement des épreuves', error);
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
                } else if (a.display_order) {
                    return -1;
                } else if (b.display_order) {
                    return 1;
                }
                return 0;
            });

            this.selectedRace = '';
            this.results = [];
            this.waves = [];
        },

        async onRaceChange() {
            if (this.selectedRace) {
                await this.loadWaves();
                await this.loadResults();
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
                this.results = [];
                this.waves = [];
            }
        },

        async loadWaves() {
            try {
                const response = await axios.get(`/waves/race/${this.selectedRace}`);
                this.waves = response.data;
            } catch (error) {
                console.error('Erreur lors du chargement des vagues', error);
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
            } catch (error) {
                console.error('Erreur lors du chargement des résultats', error);
            } finally {
                this.loading = false;
            }
        },

        async addTime() {
            if (!this.bibNumber || !this.selectedRace) return;

            this.saving = true;
            this.errorMessage = null;

            try {
                const response = await axios.post('/results/time', {
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
                this.errorMessage = error.response?.data?.message || 'Erreur lors de l\'enregistrement du temps';
            } finally {
                this.saving = false;
            }
        },

        async updateStatus(result, newStatus) {
            try {
                await axios.put(`/results/${result.id}`, {
                    status: newStatus
                });
                result.status = newStatus;
                this.successMessage = 'Statut mis à jour';
                setTimeout(() => {
                    this.successMessage = null;
                }, 2000);
            } catch (error) {
                this.errorMessage = 'Erreur lors de la mise à jour du statut';
            }
        },

        async deleteResult(result) {
            if (!confirm(`Supprimer la détection du dossard ${result.entrant?.bib_number} ?`)) return;

            try {
                await axios.delete(`/results/${result.id}`);
                this.successMessage = 'Détection supprimée';
                await this.loadResults();
            } catch (error) {
                this.errorMessage = 'Erreur lors de la suppression';
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
                this.errorMessage = 'Erreur lors du recalcul des positions';
            } finally {
                this.recalculating = false;
            }
        },

        async topDepart(race) {
            if (!confirm(`Donner le TOP DÉPART pour l'épreuve "${race.name}" ?\n\nL'heure actuelle sera enregistrée comme heure de départ.`)) return;

            this.startingRace = true;
            this.errorMessage = null;

            try {
                await axios.post(`/races/${race.id}/start`);
                race.start_time = new Date().toISOString();
                this.successMessage = `🚀 TOP DÉPART donné pour "${race.name}" !`;

                await this.loadRaces();
                await this.onEventChange();

                setTimeout(() => {
                    this.successMessage = null;
                }, 5000);
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Erreur lors du TOP DÉPART';
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
                this.autoRefreshInterval = null;
            }
        },

        formatTime(datetime) {
            if (!datetime) return 'N/A';
            const date = new Date(datetime);
            return date.toLocaleTimeString('fr-FR');
        },

        formatDuration(seconds) {
            if (!seconds) return 'N/A';
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }
    }
}
</script>

<style>
/* Modern Timing Interface Styles */
.timing-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
}

/* Header */
.timing-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem 2rem;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.header-content {
    flex: 1;
}

.header-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.header-title h1 {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0;
}

.pulse-dot {
    width: 12px;
    height: 12px;
    background: #10b981;
    border-radius: 50%;
    animation: pulse 2s infinite;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}

.header-subtitle {
    color: #64748b;
    margin: 0;
    font-size: 0.95rem;
}

.btn-recalculate {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-recalculate:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
}

.btn-recalculate:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Alerts */
.alert-success-modern,
.alert-error-modern {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
    animation: slideInDown 0.3s ease;
}

.alert-success-modern {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.alert-error-modern {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.alert-close {
    margin-left: auto;
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.alert-close:hover {
    opacity: 1;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Grid Layout */
.timing-grid {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 1.5rem;
}

.left-panel,
.right-panel {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Glass Card */
.glass-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.glass-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
}

.card-header-modern {
    padding: 1.25rem 1.5rem;
    color: white;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 700;
}

.card-header-modern h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
}

.gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gradient-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.gradient-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.gradient-info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.gradient-dark {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
}

.card-body-modern {
    padding: 1.5rem;
}

/* Form Elements */
.form-group-modern {
    margin-bottom: 1.25rem;
}

.form-group-modern label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #334155;
    font-size: 0.9rem;
}

.select-modern,
.input-modern {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: white;
}

.select-modern:focus,
.input-modern:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.input-large {
    font-size: 1.5rem;
    font-weight: 700;
    text-align: center;
    padding: 1rem;
}

/* Race Info Badge */
.race-info-badge {
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.order-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: white;
    border-radius: 10px;
    font-weight: 700;
}

.race-info-badge h4 {
    margin: 0;
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 700;
}

.start-time-info {
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-radius: 12px;
    margin-bottom: 1rem;
    color: #1e40af;
    font-weight: 600;
}

/* Start Race Button */
.btn-start-race {
    width: 100%;
    padding: 1.5rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 16px;
    font-size: 1.5rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
}

.btn-start-race:hover:not(:disabled) {
    transform: scale(1.05);
    box-shadow: 0 15px 40px rgba(16, 185, 129, 0.4);
}

.btn-start-race:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-start-race.started {
    background: linear-gradient(135deg, #64748b 0%, #475569 100%);
}

.btn-icon {
    font-size: 2.5rem;
}

/* Waves List */
.waves-list {
    padding: 0.5rem;
}

.wave-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: #f8fafc;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.wave-item:hover {
    background: #f1f5f9;
    transform: translateX(4px);
}

.wave-info strong {
    display: block;
    color: #1e293b;
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.wave-time {
    color: #64748b;
    font-size: 0.85rem;
}

.wave-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.25rem;
}

.wave-badge.active {
    background: #10b981;
    color: white;
}

.wave-badge.pending {
    background: #f59e0b;
    color: white;
}

.wave-count {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    font-weight: 700;
}

/* Quick Entry */
.quick-entry-input {
    margin-bottom: 1rem;
}

.btn-submit-time {
    width: 100%;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.btn-submit-time:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3);
}

.btn-submit-time:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.input-hint {
    text-align: center;
    color: #64748b;
    font-size: 0.85rem;
    margin: 0;
}

/* Results Card */
.results-card {
    height: fit-content;
    min-height: 600px;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.count-badge {
    padding: 0.25rem 0.75rem;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 700;
}

.btn-refresh {
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 600;
}

.btn-refresh:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.3);
}

.btn-refresh:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.results-body {
    padding: 1.5rem;
    min-height: 500px;
}

/* Empty & Loading States */
.empty-state,
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    color: #94a3b8;
}

.empty-state i,
.loading-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state p,
.loading-state p {
    font-size: 1.1rem;
    margin: 0;
}

/* Spinners */
.spinner,
.spinner-sm,
.spinner-large {
    display: inline-block;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.spinner {
    width: 16px;
    height: 16px;
}

.spinner-sm {
    width: 14px;
    height: 14px;
}

.spinner-large {
    width: 48px;
    height: 48px;
    border-width: 4px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Results Table */
.results-table-container {
    overflow-x: auto;
}

.results-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 0.5rem;
}

.results-table thead th {
    padding: 0.75rem 1rem;
    text-align: left;
    color: #64748b;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.result-row {
    background: #f8fafc;
    transition: all 0.2s ease;
}

.result-row:hover {
    background: #f1f5f9;
    transform: scale(1.01);
}

.result-row td {
    padding: 1rem;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

.result-row td:first-child {
    border-left: 1px solid #e2e8f0;
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
}

.result-row td:last-child {
    border-right: 1px solid #e2e8f0;
    border-top-right-radius: 12px;
    border-bottom-right-radius: 12px;
}

.time-cell {
    font-size: 0.9rem;
    color: #64748b;
    font-weight: 500;
}

.manual-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: #06b6d4;
    color: white;
    border-radius: 6px;
    font-size: 0.7rem;
    margin-left: 0.5rem;
}

.bib-number {
    font-size: 1.1rem;
    font-weight: 800;
    color: #1e293b;
}

.participant-name {
    color: #475569;
    font-weight: 500;
}

.wave-tag,
.lap-tag {
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
}

.wave-tag {
    background: #e2e8f0;
    color: #475569;
}

.lap-tag {
    background: #dbeafe;
    color: #1e40af;
}

.duration-cell {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    font-family: 'Courier New', monospace;
}

.speed-cell {
    color: #64748b;
    font-weight: 600;
}

/* Status Select */
.status-select {
    padding: 0.4rem 0.75rem;
    border: 2px solid;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.status-v {
    background: #10b981;
    border-color: #059669;
    color: white;
}

.status-dns {
    background: #f59e0b;
    border-color: #d97706;
    color: white;
}

.status-dnf {
    background: #ef4444;
    border-color: #dc2626;
    color: white;
}

.status-dsq {
    background: #dc2626;
    border-color: #b91c1c;
    color: white;
}

.status-ns {
    background: #6b7280;
    border-color: #4b5563;
    color: white;
}

.btn-delete {
    padding: 0.5rem 0.75rem;
    background: transparent;
    color: #ef4444;
    border: 2px solid #fecaca;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-delete:hover {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
}

/* Responsive */
@media (max-width: 1200px) {
    .timing-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection
