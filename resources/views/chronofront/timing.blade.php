@extends('chronofront.layout')

@section('title', 'Chronométrage')

@section('content')
<div class="container-fluid" x-data="timingManager()">
    <!-- Network Status Bar (Fixed Top) -->
    <div class="network-status-bar" :class="{
        'status-online': networkStatus === 'online',
        'status-offline': networkStatus === 'offline',
        'status-syncing': networkStatus === 'syncing'
    }">
        <div class="container-fluid d-flex align-items-center justify-content-between py-2">
            <div class="d-flex align-items-center gap-3">
                <div class="status-indicator">
                    <template x-if="networkStatus === 'online'">
                        <div><i class="bi bi-wifi"></i> <strong>En ligne</strong></div>
                    </template>
                    <template x-if="networkStatus === 'offline'">
                        <div><i class="bi bi-wifi-off"></i> <strong>Hors ligne</strong> - Les temps sont sauvegardés localement</div>
                    </template>
                    <template x-if="networkStatus === 'syncing'">
                        <div>
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            <strong>Synchronisation en cours...</strong>
                            <span x-show="pendingCount > 0" class="badge bg-light text-dark ms-2" x-text="pendingCount + ' en attente'"></span>
                        </div>
                    </template>
                </div>
                <div x-show="selectedRace" class="text-white-50 small">
                    <i class="bi bi-people-fill"></i> <span x-text="results.length"></span> détections
                    <span x-show="lastDetection" class="ms-3">
                        <i class="bi bi-clock-history"></i> Dernier: <strong x-text="lastDetection"></strong>
                    </span>
                </div>
            </div>
            <div>
                <button class="btn btn-sm btn-light" @click="recalculatePositions" :disabled="!selectedRace || recalculating">
                    <i class="bi bi-calculator"></i>
                    <span x-show="!recalculating">Recalculer</span>
                    <span x-show="recalculating">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <div class="content-with-status-bar">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h2"><i class="bi bi-stopwatch text-warning"></i> Chronométrage Temps Réel</h1>
                <p class="text-muted">Enregistrez les temps de passage des participants</p>
            </div>
        </div>

    <!-- Alert Messages -->
    <div x-show="successMessage" x-transition class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> <span x-text="successMessage"></span>
        <button type="button" class="btn-close" @click="successMessage = null"></button>
    </div>

    <div x-show="errorMessage" x-transition class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <span x-text="errorMessage"></span>
        <button type="button" class="btn-close" @click="errorMessage = null"></button>
    </div>

    <div class="row">
        <!-- Left Column: Race Selection & Quick Entry -->
        <div class="col-lg-4">
            <!-- Race Selection -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> Sélection Épreuve</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Événement</label>
                        <select class="form-select" x-model="selectedEvent" @change="onEventChange">
                            <option value="">-- Sélectionnez --</option>
                            <template x-for="event in events" :key="event.id">
                                <option :value="event.id" x-text="event.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Épreuve</label>
                        <select class="form-select" x-model="selectedRace" @change="onRaceChange">
                            <option value="">-- Sélectionnez --</option>
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

            <!-- TOP Départ Button -->
            <div x-show="selectedRace" class="card shadow-sm mb-3 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-flag-fill"></i> Départ Épreuve</h5>
                </div>
                <div class="card-body text-center">
                    <template x-for="race in filteredRaces" :key="race.id">
                        <div x-show="race.id == selectedRace">
                            <div class="mb-3">
                                <h6 class="mb-1">Parcours sélectionné :</h6>
                                <h5 class="text-primary">
                                    <span x-show="race.display_order" class="badge bg-dark me-2" x-text="'#' + race.display_order"></span>
                                    <strong x-text="race.name"></strong>
                                </h5>
                                <div x-show="race.start_time" class="alert alert-info mt-2 mb-0">
                                    <i class="bi bi-clock-history"></i> Départ donné le : <strong x-text="formatTime(race.start_time)"></strong>
                                </div>
                            </div>
                            <button
                                class="btn btn-lg w-100"
                                :class="race.start_time ? 'btn-secondary' : 'btn-success'"
                                @click="topDepart(race)"
                                :disabled="race.start_time || startingRace"
                            >
                                <i class="bi bi-flag-fill" style="font-size: 1.5rem;"></i>
                                <div class="mt-2" style="font-size: 1.2rem;">
                                    <span x-show="!race.start_time && !startingRace">🚀 TOP DÉPART 🚀</span>
                                    <span x-show="race.start_time">✓ Départ déjà donné</span>
                                    <span x-show="startingRace">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Enregistrement...
                                    </span>
                                </div>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Active Waves -->
            <div x-show="selectedRace && waves.length > 0" class="card shadow-sm mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-flag-fill"></i> Vagues Actives</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <template x-for="wave in waves" :key="wave.id">
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong x-text="wave.name"></strong>
                                        <span x-show="wave.is_started && !wave.end_time" class="badge bg-success ms-2">
                                            <i class="bi bi-play-fill"></i> En cours
                                        </span>
                                        <span x-show="!wave.is_started" class="badge bg-warning ms-2">
                                            <i class="bi bi-clock"></i> Pas démarrée
                                        </span>
                                        <div class="small text-muted" x-show="wave.start_time">
                                            Départ: <span x-text="formatTime(wave.start_time)"></span>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary" x-text="(wave.entrants?.length || 0)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Quick Time Entry -->
            <div x-show="selectedRace" class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> Saisie Rapide</h5>
                </div>
                <div class="card-body">
                    <form @submit.prevent="addTime">
                        <div class="mb-3">
                            <label class="form-label">Numéro de dossard</label>
                            <input
                                type="text"
                                class="form-control form-control-lg"
                                x-model="bibNumber"
                                placeholder="Ex: 2113"
                                autofocus
                                :disabled="!selectedRace || saving"
                            >
                        </div>
                        <button
                            type="submit"
                            class="btn btn-warning btn-lg w-100"
                            :disabled="!bibNumber || !selectedRace || saving"
                        >
                            <span x-show="!saving">
                                <i class="bi bi-stopwatch"></i> Enregistrer Temps
                            </span>
                            <span x-show="saving">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Enregistrement...
                            </span>
                        </button>
                        <div class="form-text">
                            Entrez le dossard et appuyez sur Entrée ou cliquez sur le bouton
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Results Table -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-list-check"></i> Dernières Détections
                        <span x-show="results.length > 0" class="badge bg-primary ms-2" x-text="results.length"></span>
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" @click="loadResults" :disabled="!selectedRace">
                            <i class="bi bi-arrow-clockwise"></i> Actualiser
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div x-show="!selectedRace" class="text-center py-5 text-muted">
                        <i class="bi bi-info-circle" style="font-size: 3rem;"></i>
                        <p class="mt-3">Veuillez sélectionner une épreuve</p>
                    </div>

                    <div x-show="selectedRace && loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>

                    <div x-show="selectedRace && !loading && results.length === 0" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">Aucune détection enregistrée</p>
                    </div>

                    <div x-show="selectedRace && !loading && results.length > 0" class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Heure</th>
                                    <th>Dossard</th>
                                    <th>Participant</th>
                                    <th>Vague</th>
                                    <th>Tour</th>
                                    <th>Temps</th>
                                    <th>Vitesse</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="result in results" :key="result.id">
                                    <tr>
                                        <td>
                                            <span class="small" x-text="formatTime(result.raw_time)"></span>
                                            <span x-show="result.is_manual" class="badge bg-info ms-1" title="Saisie manuelle">
                                                <i class="bi bi-pencil-fill"></i>
                                            </span>
                                        </td>
                                        <td>
                                            <strong x-text="result.entrant?.bib_number"></strong>
                                        </td>
                                        <td>
                                            <span x-text="result.entrant?.firstname + ' ' + result.entrant?.lastname"></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary" x-text="result.wave?.name"></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary" x-text="'Tour ' + result.lap_number"></span>
                                        </td>
                                        <td>
                                            <strong x-text="formatDuration(result.calculated_time)"></strong>
                                        </td>
                                        <td>
                                            <span x-text="result.speed ? result.speed + ' km/h' : 'N/A'"></span>
                                        </td>
                                        <td>
                                            <select
                                                class="form-select form-select-sm"
                                                :class="{
                                                    'badge-status-v': result.status === 'V',
                                                    'badge-status-dns': result.status === 'DNS',
                                                    'badge-status-dnf': result.status === 'DNF',
                                                    'badge-status-dsq': result.status === 'DSQ',
                                                    'badge-status-ns': result.status === 'NS'
                                                }"
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
                                                class="btn btn-sm btn-outline-danger"
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
</div>

<!-- Success Flash Overlay -->
<div x-show="showSuccessFlash" x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
     class="success-flash-overlay">
    <div class="success-flash-content">
        <i class="bi bi-check-circle-fill"></i>
        <div class="mt-3">
            <h4>Temps enregistré !</h4>
            <p class="mb-0" x-text="'Dossard ' + lastRecordedBib"></p>
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
        networkStatus: 'online', // 'online', 'offline', 'syncing'
        pendingTimes: [],
        pendingCount: 0,
        lastDetection: null,
        showSuccessFlash: false,
        lastRecordedBib: null,
        syncInterval: null,
        audioEnabled: true,

        init() {
            this.loadEvents();
            this.loadRaces();
            this.loadPendingTimes();
            this.startNetworkMonitoring();
            this.startSyncLoop();
        },

        // Load pending times from LocalStorage
        loadPendingTimes() {
            const stored = localStorage.getItem('chronofront_pending_times');
            if (stored) {
                this.pendingTimes = JSON.parse(stored);
                this.pendingCount = this.pendingTimes.length;
                if (this.pendingCount > 0) {
                    console.log(`📦 ${this.pendingCount} temps en attente de synchronisation`);
                }
            }
        },

        // Save pending times to LocalStorage
        savePendingTimes() {
            localStorage.setItem('chronofront_pending_times', JSON.stringify(this.pendingTimes));
            this.pendingCount = this.pendingTimes.length;
        },

        // Monitor network status
        startNetworkMonitoring() {
            // Check online/offline events
            window.addEventListener('online', () => {
                console.log('🟢 Connexion rétablie');
                this.networkStatus = 'online';
                this.syncPendingTimes();
            });

            window.addEventListener('offline', () => {
                console.log('🔴 Connexion perdue');
                this.networkStatus = 'offline';
            });

            // Initial check
            this.networkStatus = navigator.onLine ? 'online' : 'offline';
        },

        // Sync loop - try to sync pending times every 10 seconds
        startSyncLoop() {
            this.syncInterval = setInterval(() => {
                if (this.pendingCount > 0 && navigator.onLine) {
                    this.syncPendingTimes();
                }
            }, 10000);
        },

        // Sync all pending times
        async syncPendingTimes() {
            if (this.pendingCount === 0) return;

            this.networkStatus = 'syncing';
            const failedTimes = [];

            for (const timeData of this.pendingTimes) {
                try {
                    await axios.post('/results/time', timeData);
                    console.log(`✅ Temps synchronisé: Dossard ${timeData.bib_number}`);
                } catch (error) {
                    console.error(`❌ Échec sync: Dossard ${timeData.bib_number}`, error);
                    failedTimes.push(timeData);
                }
            }

            this.pendingTimes = failedTimes;
            this.savePendingTimes();

            if (this.pendingCount === 0) {
                this.networkStatus = 'online';
                this.successMessage = '✅ Tous les temps ont été synchronisés';
                await this.loadResults();
                setTimeout(() => { this.successMessage = null; }, 3000);
            } else {
                this.networkStatus = 'offline';
            }
        },

        // Play success sound
        playSuccessSound() {
            if (!this.audioEnabled) return;
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIGWi78OScTgwOUKbj8LdjHAU7k9jyzXosBS1+zPLaizsKG2e57OihUBELTKXh8bllHgU2jdXzz3osBSh+zPLcizsKHGi76+ucTQwPUKXi8bZjHQU5k9jyznwsBSh9zPLaizwJH2i67OqfThAMUKTi8LVjHQU4k9byzX0tBSh7y/PbjDwKH2i67OmgThAMT6Ti8LRjHgU3k9fyznwtBSd7y/PbjDwKH2e67OmgUBALT6Pj8LRkHgU4lNfyznwtBSd7y/PajDwKH2e67OmgUBELT6Pi8LNkHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4lNfyznwtBSd7y/PajDwKHme67OmgTxELT6Ph8LNlHgU4');
            audio.play().catch(e => console.log('Audio play failed:', e));
        },

        // Show success flash
        showSuccessAnimation(bibNumber) {
            this.lastRecordedBib = bibNumber;
            this.showSuccessFlash = true;
            this.playSuccessSound();

            setTimeout(() => {
                this.showSuccessFlash = false;
            }, 2000);
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

            // Trier par display_order
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

                // Update last detection
                if (this.results.length > 0) {
                    const last = this.results[0];
                    this.lastDetection = `Dossard ${last.entrant?.bib_number || 'N/A'}`;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des résultats', error);
                // Don't show error if it's just a network issue
                if (error.code !== 'ERR_NETWORK') {
                    this.errorMessage = 'Erreur lors du chargement des résultats';
                }
            } finally {
                this.loading = false;
            }
        },

        async addTime() {
            if (!this.bibNumber || !this.selectedRace) return;

            this.saving = true;
            this.errorMessage = null;

            const timeData = {
                race_id: this.selectedRace,
                bib_number: this.bibNumber,
                is_manual: true,
                timestamp: new Date().toISOString()
            };

            const bibNumberToShow = this.bibNumber;

            try {
                // Try to send immediately
                await axios.post('/results/time', timeData);

                // Success - online
                this.showSuccessAnimation(bibNumberToShow);
                this.successMessage = `✅ Temps enregistré pour le dossard ${bibNumberToShow}`;
                this.bibNumber = '';
                await this.loadResults();

                setTimeout(() => {
                    this.successMessage = null;
                }, 3000);

            } catch (error) {
                // Failed - check if it's a network error
                if (error.code === 'ERR_NETWORK' || !navigator.onLine) {
                    // Store locally
                    this.pendingTimes.push(timeData);
                    this.savePendingTimes();
                    this.networkStatus = 'offline';

                    this.showSuccessAnimation(bibNumberToShow);
                    this.successMessage = `📦 Temps sauvegardé localement (Dossard ${bibNumberToShow}) - Sera synchronisé quand la connexion reviendra`;
                    this.bibNumber = '';

                    setTimeout(() => {
                        this.successMessage = null;
                    }, 5000);
                } else {
                    // Other error (validation, etc.)
                    this.errorMessage = error.response?.data?.message || 'Erreur lors de l\'enregistrement du temps';
                }
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

                // Reload races to get updated start times
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
            }, 5000); // Refresh every 5 seconds
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
/* Status badges */
.badge-status-v { background-color: #10B981 !important; color: white; }
.badge-status-dns { background-color: #F59E0B !important; color: white; }
.badge-status-dnf { background-color: #EF4444 !important; color: white; }
.badge-status-dsq { background-color: #DC2626 !important; color: white; }
.badge-status-ns { background-color: #6B7280 !important; color: white; }

/* Network Status Bar */
.network-status-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.network-status-bar.status-online {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
}

.network-status-bar.status-offline {
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
    color: white;
}

.network-status-bar.status-syncing {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
    color: white;
}

.content-with-status-bar {
    padding-top: 60px;
}

/* Success Flash Overlay */
.success-flash-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    background: rgba(16, 185, 129, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.success-flash-content {
    text-align: center;
    color: white;
    animation: successPulse 0.5s ease-out;
}

.success-flash-content i {
    font-size: 5rem;
    animation: successCheckmark 0.6s ease-out;
}

.success-flash-content h4 {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
}

.success-flash-content p {
    font-size: 1.5rem;
    opacity: 0.9;
}

@keyframes successPulse {
    0% { transform: scale(0.8); opacity: 0; }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes successCheckmark {
    0% { transform: scale(0) rotate(-45deg); opacity: 0; }
    50% { transform: scale(1.2) rotate(0deg); }
    100% { transform: scale(1) rotate(0deg); opacity: 1; }
}

/* Table row animation */
@keyframes slideInFade {
    0% {
        opacity: 0;
        transform: translateX(-20px);
        background-color: rgba(16, 185, 129, 0.3);
    }
    100% {
        opacity: 1;
        transform: translateX(0);
        background-color: transparent;
    }
}

tbody tr {
    animation: slideInFade 0.5s ease-out;
}

tbody tr:first-child {
    background-color: rgba(16, 185, 129, 0.1);
}

/* Status indicator pulse */
.network-status-bar.status-syncing .spinner-border {
    animation: spin 0.75s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
@endsection
