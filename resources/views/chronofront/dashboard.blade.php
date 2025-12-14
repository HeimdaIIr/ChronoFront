@extends('chronofront.layout')

@section('title', 'Tableau de bord')

@section('content')
<div x-data="dashboard()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="bi bi-house-door text-primary"></i> Tableau de bord</h1>
            <p class="text-muted">Vue d'ensemble de votre système de chronométrage</p>
        </div>
        <div>
            <span class="text-muted">{{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #3B82F6 0%, #2563eb 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 x-text="stats.events || 0"></h3>
                        <p>Événements</p>
                    </div>
                    <i class="bi bi-calendar-event" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 x-text="stats.races || 0"></h3>
                        <p>Épreuves</p>
                    </div>
                    <i class="bi bi-trophy" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 x-text="stats.entrants || 0"></h3>
                        <p>Participants</p>
                    </div>
                    <i class="bi bi-people" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 x-text="stats.results || 0"></h3>
                        <p>Résultats</p>
                    </div>
                    <i class="bi bi-bar-chart" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-lightning-charge text-warning"></i> Actions rapides
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('events') }}" class="btn btn-primary w-100 py-3">
                                <i class="bi bi-plus-circle"></i><br>
                                <span class="mt-2">Nouvel événement</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('entrants.import') }}" class="btn btn-success w-100 py-3">
                                <i class="bi bi-upload"></i><br>
                                <span class="mt-2">Import CSV</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('timing') }}" class="btn btn-warning w-100 py-3">
                                <i class="bi bi-stopwatch"></i><br>
                                <span class="mt-2">Chronométrer</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('results') }}" class="btn btn-info w-100 py-3 text-white">
                                <i class="bi bi-trophy"></i><br>
                                <span class="mt-2">Voir résultats</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Events -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-event text-primary"></i> Événements récents</span>
                    <a href="{{ route('events') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body">
                    <template x-if="recentEvents.length === 0">
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">Aucun événement</p>
                            <a href="{{ route('events') }}" class="btn btn-sm btn-primary">Créer un événement</a>
                        </div>
                    </template>

                    <div class="list-group list-group-flush">
                        <template x-for="event in recentEvents" :key="event.id">
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1" x-text="event.name"></h6>
                                    <small class="text-muted" x-text="new Date(event.date_start).toLocaleDateString('fr-FR')"></small>
                                </div>
                                <p class="mb-1 small text-muted" x-text="event.location"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle text-info"></i> Guide de démarrage rapide
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">
                            <strong>Créer un événement</strong>
                            <p class="small text-muted mb-0">Commencez par créer votre événement sportif</p>
                        </li>
                        <li class="mb-2">
                            <strong>Ajouter des épreuves</strong>
                            <p class="small text-muted mb-0">Définissez les parcours et distances</p>
                        </li>
                        <li class="mb-2">
                            <strong>Importer les participants</strong>
                            <p class="small text-muted mb-0">Chargez votre fichier CSV avec les inscrits</p>
                        </li>
                        <li class="mb-2">
                            <strong>Créer des vagues</strong> (optionnel)
                            <p class="small text-muted mb-0">Organisez les départs par vagues</p>
                        </li>
                        <li class="mb-2">
                            <strong>Chronométrer</strong>
                            <p class="small text-muted mb-0">Enregistrez les temps de passage</p>
                        </li>
                        <li>
                            <strong>Consulter les résultats</strong>
                            <p class="small text-muted mb-0">Visualisez et exportez les classements</p>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function dashboard() {
    return {
        stats: {
            events: 0,
            races: 0,
            entrants: 0,
            results: 0
        },
        recentEvents: [],

        init() {
            this.loadStats();
            this.loadRecentEvents();
        },

        async loadStats() {
            try {
                // Load events count
                const eventsResponse = await axios.get('/events');
                this.stats.events = eventsResponse.data.length || 0;

                // Load races count
                const racesResponse = await axios.get('/races');
                this.stats.races = racesResponse.data.length || 0;

                // Load entrants count
                const entrantsResponse = await axios.get('/entrants');
                this.stats.entrants = entrantsResponse.data.length || 0;

            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        async loadRecentEvents() {
            try {
                const response = await axios.get('/events');
                this.recentEvents = response.data.slice(0, 5);
            } catch (error) {
                console.error('Error loading recent events:', error);
            }
        }
    }
}
</script>
@endsection
