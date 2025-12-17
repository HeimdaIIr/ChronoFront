@extends('chronofront.layout')

@section('title', 'Résultats')

@section('content')
<div class="container-fluid" x-data="resultsManager()">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2"><i class="bi bi-bar-chart text-success"></i> Résultats et Classements</h1>
            <p class="text-muted">Consultez les classements et exportez les résultats</p>
        </div>
        <div class="col-auto">
            <button
                class="btn btn-primary me-2"
                @click="recalculatePositions"
                :disabled="recalculating"
            >
                <span x-show="!recalculating">
                    <i class="bi bi-calculator"></i> Recalculer
                </span>
                <span x-show="recalculating">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Calcul...
                </span>
            </button>
            <button
                class="btn btn-success me-2"
                @click="exportResults"
                :disabled="!selectedRace || results.length === 0"
            >
                <i class="bi bi-download"></i> Exporter CSV
            </button>
            <button
                class="btn btn-danger me-2"
                @click="downloadPDF"
                :disabled="!selectedRace || filteredResults.length === 0"
            >
                <i class="bi bi-file-pdf"></i> Télécharger PDF
            </button>
            <button
                class="btn btn-secondary me-2"
                @click="printResults"
                :disabled="!selectedRace || filteredResults.length === 0"
            >
                <i class="bi bi-printer"></i> Imprimer
            </button>
            <button
                class="btn btn-warning"
                @click="showAwardsModal = true"
                :disabled="!selectedRace || filteredResults.length === 0"
            >
                <i class="bi bi-trophy"></i> PDF Récompenses
            </button>
        </div>
    </div>

    <!-- Modal Configuration Récompenses -->
    <div x-show="showAwardsModal" class="modal" style="display: none;" :style="showAwardsModal && 'display: block; background: rgba(0,0,0,0.5);'">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trophy text-warning"></i> Configuration des Récompenses</h5>
                    <button type="button" class="btn-close" @click="showAwardsModal = false"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Scratch général -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Scratch Général</label>
                            <div class="input-group">
                                <span class="input-group-text">Top</span>
                                <input type="number" class="form-control" x-model="awards.topScratch" min="0" max="50">
                                <span class="input-group-text">premiers</span>
                            </div>
                            <small class="text-muted">0 = désactivé</small>
                        </div>

                        <!-- Par genre -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Par Genre (F/H)</label>
                            <div class="input-group">
                                <span class="input-group-text">Top</span>
                                <input type="number" class="form-control" x-model="awards.topGender" min="0" max="50">
                                <span class="input-group-text">par genre</span>
                            </div>
                            <small class="text-muted">Ex: Top 3 F + Top 3 H</small>
                        </div>

                        <!-- Par catégorie -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Par Catégorie</label>
                            <div class="input-group">
                                <span class="input-group-text">Top</span>
                                <input type="number" class="form-control" x-model="awards.topCategory" min="0" max="50">
                                <span class="input-group-text">par catégorie</span>
                            </div>
                            <small class="text-muted">Ex: 1er de chaque catégorie</small>
                        </div>

                        <!-- Par genre ET catégorie -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Par Genre ET Catégorie</label>
                            <div class="input-group">
                                <span class="input-group-text">Top</span>
                                <input type="number" class="form-control" x-model="awards.topGenderCategory" min="0" max="50">
                                <span class="input-group-text">F/H par cat.</span>
                            </div>
                            <small class="text-muted">Ex: 1er F + 1er H par catégorie</small>
                        </div>
                    </div>

                    <!-- Exemples -->
                    <div class="alert alert-info mt-3">
                        <strong>Exemples :</strong>
                        <ul class="mb-0">
                            <li><strong>Exemple 1 :</strong> Top 3 scratch + 1er par catégorie → Top Scratch: 3, Top Catégorie: 1</li>
                            <li><strong>Exemple 2 :</strong> Top 3 F + Top 3 H + 1er F/H par catégorie → Top Genre: 3, Top Genre ET Catégorie: 1</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showAwardsModal = false">Annuler</button>
                    <button type="button" class="btn btn-success" @click="downloadAwardsPDF">
                        <i class="bi bi-file-pdf"></i> Télécharger PDF
                    </button>
                    <button type="button" class="btn btn-primary" @click="printAwardsPDF">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Événement</label>
                    <select class="form-select" x-model="selectedEvent" @change="onEventChange">
                        <option value="">-- Sélectionnez --</option>
                        <template x-for="event in events" :key="event.id">
                            <option :value="event.id" x-text="event.name"></option>
                        </template>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Épreuve</label>
                    <select class="form-select" x-model="selectedRace" @change="onRaceChange">
                        <option value="">-- Sélectionnez --</option>
                        <template x-for="race in filteredRaces" :key="race.id">
                            <option :value="race.id" x-text="race.name"></option>
                        </template>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Affichage</label>
                    <select class="form-select" x-model="displayMode" @change="filterResults">
                        <option value="general">Général</option>
                        <option value="category">Par catégorie</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select class="form-select" x-model="statusFilter" @change="filterResults">
                        <option value="all">Tous</option>
                        <option value="V">Validés uniquement</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div x-show="selectedRace && results.length > 0" class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-people-fill text-primary" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-0" x-text="stats.total"></h3>
                    <p class="text-muted mb-0">Participants</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-0" x-text="stats.finished"></h3>
                    <p class="text-muted mb-0">Arrivés</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-stopwatch-fill text-warning" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-0" x-text="formatDuration(stats.avgTime)"></h3>
                    <p class="text-muted mb-0">Temps moyen</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-speedometer2 text-info" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-0" x-text="stats.avgSpeed ? stats.avgSpeed.toFixed(2) + ' km/h' : 'N/A'"></h3>
                    <p class="text-muted mb-0">Vitesse moyenne</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Message si aucun événement sélectionné -->
            <div x-show="!selectedEvent && !selectedRace" class="text-center py-5 text-muted">
                <i class="bi bi-info-circle" style="font-size: 3rem;"></i>
                <p class="mt-3">Veuillez sélectionner un événement</p>
            </div>

            <!-- Loading -->
            <div x-show="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>

            <!-- Résultats groupés par parcours (quand seul l'événement est sélectionné) -->
            <div x-show="selectedEvent && !selectedRace && !loading && Object.keys(resultsByRace).length > 0">
                <template x-for="(raceData, raceId) in resultsByRace" :key="raceId">
                    <div class="mb-5">
                        <h4 class="border-bottom pb-2 mb-3">
                            <i class="bi bi-flag-fill text-primary"></i>
                            <span x-text="raceData.race.name"></span>
                            <span class="badge bg-secondary ms-2" x-text="raceData.results.length + ' participants'"></span>
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pos.</th>
                                        <th>Dossard</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Sexe</th>
                                        <th>Catégorie</th>
                                        <th>Club</th>
                                        <th>Temps</th>
                                        <th>Vitesse</th>
                                        <th>Pos. Cat.</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="result in raceData.results" :key="result.id">
                                        <tr>
                                            <td><strong x-text="result.position || '-'"></strong></td>
                                            <td><span class="badge bg-primary" x-text="result.entrant?.bib_number"></span></td>
                                            <td x-text="result.entrant?.lastname"></td>
                                            <td x-text="result.entrant?.firstname"></td>
                                            <td x-text="result.entrant?.gender"></td>
                                            <td><span class="badge bg-info" x-text="result.entrant?.category?.name || 'N/A'"></span></td>
                                            <td x-text="result.entrant?.club || '-'"></td>
                                            <td><strong x-text="formatDuration(result.calculated_time)"></strong></td>
                                            <td x-text="result.speed ? result.speed + ' km/h' : 'N/A'"></td>
                                            <td x-text="result.category_position || '-'"></td>
                                            <td>
                                                <span
                                                    class="badge"
                                                    :class="{
                                                        'badge-status-v': result.status === 'V',
                                                        'badge-status-dns': result.status === 'DNS',
                                                        'badge-status-dnf': result.status === 'DNF',
                                                        'badge-status-dsq': result.status === 'DSQ',
                                                        'badge-status-ns': result.status === 'NS'
                                                    }"
                                                    x-text="result.status"
                                                ></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Message si aucun résultat dans l'événement -->
            <div x-show="selectedEvent && !selectedRace && !loading && Object.keys(resultsByRace).length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3">Aucun résultat disponible pour cet événement</p>
            </div>

            <!-- Message si aucun résultat pour le parcours sélectionné -->
            <div x-show="selectedRace && !loading && filteredResults.length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3">Aucun résultat disponible</p>
            </div>

            <!-- General Results -->
            <div x-show="selectedRace && !loading && filteredResults.length > 0 && displayMode === 'general'" class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Pos.</th>
                            <th>Dossard</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Sexe</th>
                            <th>Catégorie</th>
                            <th>Club</th>
                            <th>Temps</th>
                            <th>Vitesse</th>
                            <th>Pos. Cat.</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="result in filteredResults" :key="result.id">
                            <tr>
                                <td>
                                    <strong x-text="result.position || '-'"></strong>
                                </td>
                                <td>
                                    <span class="badge bg-primary" x-text="result.entrant?.bib_number"></span>
                                </td>
                                <td x-text="result.entrant?.lastname"></td>
                                <td x-text="result.entrant?.firstname"></td>
                                <td>
                                    <span x-text="result.entrant?.gender"></span>
                                </td>
                                <td>
                                    <span class="badge bg-info" x-text="result.entrant?.category?.name || 'N/A'"></span>
                                </td>
                                <td x-text="result.entrant?.club || '-'"></td>
                                <td>
                                    <strong x-text="formatDuration(result.calculated_time)"></strong>
                                </td>
                                <td x-text="result.speed ? result.speed + ' km/h' : 'N/A'"></td>
                                <td x-text="result.category_position || '-'"></td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="{
                                            'badge-status-v': result.status === 'V',
                                            'badge-status-dns': result.status === 'DNS',
                                            'badge-status-dnf': result.status === 'DNF',
                                            'badge-status-dsq': result.status === 'DSQ',
                                            'badge-status-ns': result.status === 'NS'
                                        }"
                                        x-text="result.status"
                                    ></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Category Results -->
            <div x-show="selectedRace && !loading && filteredResults.length > 0 && displayMode === 'category'">
                <template x-for="(categoryResults, categoryName) in resultsByCategory" :key="categoryName">
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="bi bi-trophy-fill text-warning"></i>
                            <span x-text="categoryName"></span>
                            <span class="badge bg-secondary ms-2" x-text="categoryResults.length + ' participants'"></span>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pos. Cat.</th>
                                        <th>Pos. Général</th>
                                        <th>Dossard</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Club</th>
                                        <th>Temps</th>
                                        <th>Vitesse</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="result in categoryResults" :key="result.id">
                                        <tr>
                                            <td>
                                                <strong x-text="result.category_position || '-'"></strong>
                                            </td>
                                            <td x-text="result.position || '-'"></td>
                                            <td>
                                                <span class="badge bg-primary" x-text="result.entrant?.bib_number"></span>
                                            </td>
                                            <td x-text="result.entrant?.lastname"></td>
                                            <td x-text="result.entrant?.firstname"></td>
                                            <td x-text="result.entrant?.club || '-'"></td>
                                            <td>
                                                <strong x-text="formatDuration(result.calculated_time)"></strong>
                                            </td>
                                            <td x-text="result.speed ? result.speed + ' km/h' : 'N/A'"></td>
                                            <td>
                                                <span
                                                    class="badge"
                                                    :class="{
                                                        'badge-status-v': result.status === 'V',
                                                        'badge-status-dns': result.status === 'DNS',
                                                        'badge-status-dnf': result.status === 'DNF',
                                                        'badge-status-dsq': result.status === 'DSQ',
                                                        'badge-status-ns': result.status === 'NS'
                                                    }"
                                                    x-text="result.status"
                                                ></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function resultsManager() {
    return {
        events: [],
        races: [],
        filteredRaces: [],
        results: [],
        filteredResults: [],
        resultsByCategory: {},
        resultsByRace: {}, // Résultats groupés par parcours
        selectedEvent: '',
        selectedRace: '',
        displayMode: 'general',
        statusFilter: 'all',
        loading: false,
        recalculating: false,
        showAwardsModal: false,
        awards: {
            topScratch: 3,
            topGender: 0,
            topCategory: 1,
            topGenderCategory: 0
        },
        stats: {
            total: 0,
            finished: 0,
            avgTime: 0,
            avgSpeed: 0
        },

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

        async onEventChange() {
            if (this.selectedEvent) {
                this.filteredRaces = this.races.filter(race => race.event_id == this.selectedEvent);
                // Charger les résultats de toutes les épreuves de l'événement
                await this.loadEventResults();
            } else {
                this.filteredRaces = this.races;
                this.resultsByRace = {};
            }
            this.selectedRace = '';
            this.results = [];
        },

        async onRaceChange() {
            if (this.selectedRace) {
                await this.loadResults();
                this.resultsByRace = {}; // Clear event results when selecting a specific race
            } else if (this.selectedEvent) {
                // Si on désélectionne le parcours mais qu'un événement est sélectionné
                // recharger les résultats de l'événement
                await this.loadEventResults();
            } else {
                this.results = [];
                this.filteredResults = [];
                this.resultsByRace = {};
            }
        },

        // Charger les résultats de toutes les épreuves d'un événement
        async loadEventResults() {
            if (!this.selectedEvent) return;

            this.loading = true;
            this.resultsByRace = {};

            try {
                // Charger les résultats pour chaque épreuve de l'événement
                for (const race of this.filteredRaces) {
                    const response = await axios.get(`/results/race/${race.id}`);
                    if (response.data && response.data.length > 0) {
                        // Filtrer et trier
                        let results = response.data;
                        if (this.statusFilter === 'V') {
                            results = results.filter(r => r.status === 'V');
                        }
                        results = results.sort((a, b) => (a.position || 9999) - (b.position || 9999));

                        this.resultsByRace[race.id] = {
                            race: race,
                            results: results
                        };
                    }
                }
            } catch (error) {
                console.error('Erreur lors du chargement des résultats', error);
            } finally {
                this.loading = false;
            }
        },

        async loadResults() {
            if (!this.selectedRace) return;

            this.loading = true;
            try {
                const response = await axios.get(`/results/race/${this.selectedRace}`);
                this.results = response.data;
                this.filterResults();
                this.calculateStats();
            } catch (error) {
                console.error('Erreur lors du chargement des résultats', error);
            } finally {
                this.loading = false;
            }
        },

        filterResults() {
            let filtered = this.results;

            // Filter by status
            if (this.statusFilter === 'V') {
                filtered = filtered.filter(r => r.status === 'V');
            }

            // Sort by position
            filtered = filtered.sort((a, b) => (a.position || 9999) - (b.position || 9999));

            this.filteredResults = filtered;

            // Group by category if needed
            if (this.displayMode === 'category') {
                this.resultsByCategory = filtered.reduce((acc, result) => {
                    const categoryName = result.entrant?.category?.name || 'Sans catégorie';
                    if (!acc[categoryName]) {
                        acc[categoryName] = [];
                    }
                    acc[categoryName].push(result);
                    return acc;
                }, {});

                // Sort each category by category_position
                Object.keys(this.resultsByCategory).forEach(cat => {
                    this.resultsByCategory[cat].sort((a, b) =>
                        (a.category_position || 9999) - (b.category_position || 9999)
                    );
                });
            }
        },

        calculateStats() {
            const validResults = this.results.filter(r => r.status === 'V' && r.calculated_time);

            this.stats.total = this.results.length;
            this.stats.finished = validResults.length;

            if (validResults.length > 0) {
                const totalTime = validResults.reduce((sum, r) => sum + (r.calculated_time || 0), 0);
                this.stats.avgTime = Math.round(totalTime / validResults.length);

                const resultsWithSpeed = validResults.filter(r => r.speed);
                if (resultsWithSpeed.length > 0) {
                    const totalSpeed = resultsWithSpeed.reduce((sum, r) => sum + parseFloat(r.speed), 0);
                    this.stats.avgSpeed = totalSpeed / resultsWithSpeed.length;
                } else {
                    this.stats.avgSpeed = 0;
                }
            } else {
                this.stats.avgTime = 0;
                this.stats.avgSpeed = 0;
            }
        },

        async recalculatePositions() {
            // Confirm action as this will recalculate ALL races
            if (!confirm('Recalculer les positions pour TOUTES les courses ?\n\nCette opération peut prendre quelques secondes.')) {
                return;
            }

            this.recalculating = true;
            try {
                const response = await axios.post('/results/recalculate-all');

                // Show success message
                alert(`Positions recalculées avec succès!\n\n${response.data.total_races} courses traitées\n${response.data.total_results} résultats traités`);

                // Reload results to show new positions
                if (this.selectedRace) {
                    await this.loadResults();
                }
            } catch (error) {
                console.error('Erreur lors du recalcul des positions', error);
                alert('Erreur lors du recalcul des positions: ' + (error.response?.data?.message || error.message));
            } finally {
                this.recalculating = false;
            }
        },

        exportResults() {
            if (!this.selectedRace) return;

            window.location.href = `/api/results/race/${this.selectedRace}/export`;
        },

        downloadPDF() {
            if (!this.selectedRace) return;

            // Construire l'URL avec les filtres actuels
            let url = `/api/results/race/${this.selectedRace}/pdf?display_mode=${this.displayMode}&status_filter=${this.statusFilter}`;

            window.location.href = url;
        },

        printResults() {
            if (!this.selectedRace) return;

            // Ouvrir le PDF dans un nouvel onglet avec auto-print
            let url = `/api/results/race/${this.selectedRace}/pdf?display_mode=${this.displayMode}&status_filter=${this.statusFilter}&print=true`;
            window.open(url, '_blank');
        },

        downloadAwardsPDF() {
            if (!this.selectedRace) return;

            // Construire l'URL avec les paramètres de récompenses
            const params = new URLSearchParams({
                topScratch: this.awards.topScratch,
                topGender: this.awards.topGender,
                topCategory: this.awards.topCategory,
                topGenderCategory: this.awards.topGenderCategory
            });

            window.location.href = `/api/results/race/${this.selectedRace}/awards-pdf?${params.toString()}`;
            this.showAwardsModal = false;
        },

        printAwardsPDF() {
            if (!this.selectedRace) return;

            // Construire l'URL avec les paramètres de récompenses + print=true
            const params = new URLSearchParams({
                topScratch: this.awards.topScratch,
                topGender: this.awards.topGender,
                topCategory: this.awards.topCategory,
                topGenderCategory: this.awards.topGenderCategory,
                print: 'true'
            });

            window.open(`/api/results/race/${this.selectedRace}/awards-pdf?${params.toString()}`, '_blank');
            this.showAwardsModal = false;
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
.badge-status-v { background-color: #10B981; }
.badge-status-dns { background-color: #F59E0B; }
.badge-status-dnf { background-color: #EF4444; }
.badge-status-dsq { background-color: #DC2626; }
.badge-status-ns { background-color: #6B7280; }

@media print {
    /* Masquer les éléments non nécessaires à l'impression */
    .sidebar,
    button,
    .alert,
    .card-body > div:first-child,
    .col-auto {
        display: none !important;
    }

    /* Ajuster la mise en page */
    .main-content {
        padding: 0 !important;
    }

    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }

    /* Assurer que les tableaux soient bien imprimés */
    table {
        page-break-inside: auto;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    /* Améliorer la lisibilité des badges */
    .badge {
        border: 1px solid #333;
    }

    /* Titre de la page */
    h1, h4, h5 {
        color: #000 !important;
    }
}
</style>
@endsection
