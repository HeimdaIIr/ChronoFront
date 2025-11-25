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
                class="btn btn-success"
                @click="exportResults"
                :disabled="!selectedRace || results.length === 0"
            >
                <i class="bi bi-download"></i> Exporter CSV
            </button>
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
            <div x-show="!selectedRace" class="text-center py-5 text-muted">
                <i class="bi bi-info-circle" style="font-size: 3rem;"></i>
                <p class="mt-3">Veuillez sélectionner une épreuve</p>
            </div>

            <div x-show="selectedRace && loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>

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
        selectedEvent: '',
        selectedRace: '',
        displayMode: 'general',
        statusFilter: 'all',
        loading: false,
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

        onEventChange() {
            if (this.selectedEvent) {
                this.filteredRaces = this.races.filter(race => race.event_id == this.selectedEvent);
            } else {
                this.filteredRaces = this.races;
            }
            this.selectedRace = '';
            this.results = [];
        },

        async onRaceChange() {
            if (this.selectedRace) {
                await this.loadResults();
            } else {
                this.results = [];
                this.filteredResults = [];
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

        exportResults() {
            if (!this.selectedRace) return;

            window.location.href = `/api/results/race/${this.selectedRace}/export`;
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
</style>
@endsection
