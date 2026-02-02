@extends('chronofront.layout')

@section('title', 'Tableau de bord')

@section('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endsection

@section('content')
<div x-data="dashboard()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="bi bi-house-door text-primary"></i> Tableau de bord</h1>
            <p class="text-muted">Vue d'ensemble de votre système de chronométrage</p>
        </div>
        <div>
            <span class="text-muted" x-text="currentTime"></span>
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
            <div class="stats-card" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 x-text="stats.races || 0"></h3>
                        <p>Parcours</p>
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
            <div class="stats-card" style="background: linear-gradient(135deg, #EAB308 0%, #CA8A04 100%);">
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
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-lightning-charge text-warning"></i> Actions rapides
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('events') }}" class="btn btn-primary w-100 py-3">
                                <i class="bi bi-plus-circle"></i><br>
                                <span class="mt-2">Nouvel événement</span>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('entrants.import') }}" class="btn btn-success w-100 py-3">
                                <i class="bi bi-upload"></i><br>
                                <span class="mt-2">Import CSV</span>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('timing') }}" class="btn w-100 py-3" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; border: none;">
                                <i class="bi bi-stopwatch"></i><br>
                                <span class="mt-2">Chronométrer</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-database"></i> Options Base de Données
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <a href="{{ route('database.export') }}" class="btn btn-secondary w-100 py-3" download>
                                <i class="bi bi-download"></i><br>
                                <span class="mt-2">Exporter DB</span>
                            </a>
                        </div>
                        <div class="col-12">
                            <button @click="showImportModal = true" class="btn btn-danger w-100 py-3">
                                <i class="bi bi-upload"></i><br>
                                <span class="mt-2">Importer DB</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Import DB -->
    <div x-show="showImportModal" x-cloak class="modal" tabindex="-1" :class="{'d-block': showImportModal}" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Importer une base de données</h5>
                    <button type="button" class="btn-close" @click="showImportModal = false"></button>
                </div>
                <form action="{{ route('database.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Attention !</strong> Cette action va remplacer toutes les données actuelles par celles du fichier importé.
                            Un backup automatique sera créé dans <code>storage/databases/archives/</code>.
                        </div>
                        <div class="mb-3">
                            <label for="database_file" class="form-label">Fichier SQLite (.sqlite)</label>
                            <input type="file" class="form-control" id="database_file" name="database_file" accept=".sqlite" required>
                            <div class="form-text">Sélectionnez un fichier .sqlite à importer (jusqu'à 200 Mo)</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showImportModal = false">Annuler</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-upload"></i> Importer et remplacer
                        </button>
                    </div>
                </form>
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
                            <p class="small text-muted mb-0">Commencez par créer votre événement</p>
                        </li>
                        <li class="mb-2">
                            <strong>Configurer votre/vos lecteur(s)</strong>
                            <p class="small text-muted mb-0">Attribuez le numéro du lecteur et définissez son mode</p>
                        </li>
                        <li class="mb-2">
                            <strong>Importer votre fichier d'inscription</strong>
                            <p class="small text-muted mb-0">Les parcours et vagues seront créés automatiquement</p>
                        </li>
                        <li class="mb-2">
                            <strong>Chronométrer</strong>
                            <p class="small text-muted mb-0">Place au chronométrage de votre événement</p>
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
        showImportModal: false,
        currentTime: '',

        init() {
            this.updateTime();
            setInterval(() => this.updateTime(), 1000);
            this.loadStats();
            this.loadRecentEvents();
        },

        updateTime() {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            this.currentTime = `${day}/${month}/${year} ${hours}:${minutes}`;
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

                // Load results count
                const resultsResponse = await axios.get('/results/count');
                this.stats.results = resultsResponse.data.count || 0;

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
