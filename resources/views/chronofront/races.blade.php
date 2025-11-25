@extends('chronofront.layout')

@section('title', 'Gestion des Épreuves')

@section('content')
<div class="container-fluid" x-data="racesManager()">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2"><i class="bi bi-trophy text-warning"></i> Gestion des Épreuves</h1>
            <p class="text-muted">Gérez les épreuves de vos événements sportifs</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" @click="openCreateModal">
                <i class="bi bi-plus-circle"></i> Nouvelle Épreuve
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <div x-show="successMessage" x-transition class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> <span x-text="successMessage"></span>
        <button type="button" class="btn-close" @click="successMessage = null"></button>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Filtrer par événement</label>
                    <select class="form-select" x-model="selectedEventFilter" @change="loadRaces">
                        <option value="">Tous les événements</option>
                        <template x-for="event in events" :key="event.id">
                            <option :value="event.id" x-text="event.name"></option>
                        </template>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Recherche</label>
                    <input type="text" class="form-control" x-model="searchQuery" @input="filterRaces" placeholder="Nom de l'épreuve...">
                </div>
            </div>
        </div>
    </div>

    <!-- Races Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div x-show="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>

            <div x-show="!loading && filteredRaces.length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3">Aucune épreuve trouvée</p>
            </div>

            <div x-show="!loading && filteredRaces.length > 0" class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">Ordre</th>
                            <th>Nom</th>
                            <th>Événement</th>
                            <th>Type</th>
                            <th>Distance</th>
                            <th>Tours</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="race in filteredRaces" :key="race.id">
                            <tr>
                                <td>
                                    <input
                                        type="number"
                                        class="form-control form-control-sm"
                                        :value="race.display_order || ''"
                                        @change="updateRaceOrder(race, $event.target.value)"
                                        placeholder="-"
                                        min="1"
                                        style="width: 70px;"
                                    >
                                </td>
                                <td>
                                    <strong x-text="race.name"></strong>
                                </td>
                                <td>
                                    <span class="badge bg-secondary" x-text="race.event?.name"></span>
                                </td>
                                <td>
                                    <span class="badge bg-info" x-text="race.type"></span>
                                </td>
                                <td>
                                    <span x-text="race.distance ? race.distance + ' km' : 'N/A'"></span>
                                </td>
                                <td>
                                    <span x-text="race.laps || 'N/A'"></span>
                                </td>
                                <td>
                                    <span x-show="race.start_time && !race.end_time" class="badge bg-success">
                                        <i class="bi bi-play-fill"></i> En cours
                                    </span>
                                    <span x-show="race.end_time" class="badge bg-secondary">
                                        <i class="bi bi-stop-fill"></i> Terminée
                                    </span>
                                    <span x-show="!race.start_time" class="badge bg-warning">
                                        <i class="bi bi-clock"></i> Pas démarrée
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button
                                            class="btn btn-success"
                                            @click="startRace(race)"
                                            x-show="!race.start_time"
                                            title="Démarrer"
                                        >
                                            <i class="bi bi-play-fill"></i>
                                        </button>
                                        <button
                                            class="btn btn-danger"
                                            @click="endRace(race)"
                                            x-show="race.start_time && !race.end_time"
                                            title="Terminer"
                                        >
                                            <i class="bi bi-stop-fill"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" @click="openEditModal(race)" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" @click="deleteRace(race)" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal fade" :class="{'show d-block': showModal}" tabindex="-1" style="background: rgba(0,0,0,0.5);" x-show="showModal" @click.self="closeModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="editingRace ? 'Modifier l\'épreuve' : 'Nouvelle épreuve'"></h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <form @submit.prevent="saveRace">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Événement <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="form.event_id" required>
                                    <option value="">-- Sélectionnez --</option>
                                    <template x-for="event in events" :key="event.id">
                                        <option :value="event.id" x-text="event.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nom de l'épreuve <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.name" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="form.type" required>
                                    <option value="1 passage">1 passage</option>
                                    <option value="n_laps">N tours</option>
                                    <option value="infinite_loop">Boucle infinie</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Distance (km)</label>
                                <input type="number" step="0.01" class="form-control" x-model="form.distance">
                            </div>

                            <div class="col-md-4" x-show="form.type === 'n_laps'">
                                <label class="form-label">Nombre de tours</label>
                                <input type="number" class="form-control" x-model="form.laps">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" x-model="form.description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <span x-show="!saving">Enregistrer</span>
                            <span x-show="saving">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Enregistrement...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function racesManager() {
    return {
        races: [],
        filteredRaces: [],
        events: [],
        selectedEventFilter: '',
        searchQuery: '',
        loading: false,
        showModal: false,
        editingRace: null,
        saving: false,
        successMessage: null,
        form: {
            event_id: '',
            name: '',
            type: '1 passage',
            distance: null,
            laps: null,
            description: ''
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
            this.loading = true;
            try {
                let url = '/races';
                if (this.selectedEventFilter) {
                    url += `/event/${this.selectedEventFilter}`;
                }
                const response = await axios.get(url);
                this.races = response.data;
                this.filterRaces();
            } catch (error) {
                console.error('Erreur lors du chargement des épreuves', error);
            } finally {
                this.loading = false;
            }
        },

        filterRaces() {
            if (!this.searchQuery) {
                this.filteredRaces = this.races;
            } else {
                const query = this.searchQuery.toLowerCase();
                this.filteredRaces = this.races.filter(race =>
                    race.name.toLowerCase().includes(query)
                );
            }
        },

        openCreateModal() {
            this.editingRace = null;
            this.form = {
                event_id: this.selectedEventFilter || '',
                name: '',
                type: '1 passage',
                distance: null,
                laps: null,
                description: ''
            };
            this.showModal = true;
        },

        openEditModal(race) {
            this.editingRace = race;
            this.form = {
                event_id: race.event_id,
                name: race.name,
                type: race.type,
                distance: race.distance,
                laps: race.laps,
                description: race.description || ''
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingRace = null;
        },

        async saveRace() {
            this.saving = true;
            try {
                if (this.editingRace) {
                    await axios.put(`/races/${this.editingRace.id}`, this.form);
                    this.successMessage = 'Épreuve modifiée avec succès';
                } else {
                    await axios.post('/races', this.form);
                    this.successMessage = 'Épreuve créée avec succès';
                }
                this.closeModal();
                this.loadRaces();
            } catch (error) {
                alert('Erreur lors de l\'enregistrement : ' + (error.response?.data?.message || error.message));
            } finally {
                this.saving = false;
            }
        },

        async startRace(race) {
            if (!confirm(`Démarrer l'épreuve "${race.name}" ?`)) return;

            try {
                await axios.post(`/races/${race.id}/start`);
                this.successMessage = `Épreuve "${race.name}" démarrée`;
                this.loadRaces();
            } catch (error) {
                alert('Erreur lors du démarrage : ' + (error.response?.data?.message || error.message));
            }
        },

        async endRace(race) {
            if (!confirm(`Terminer l'épreuve "${race.name}" ?`)) return;

            try {
                await axios.post(`/races/${race.id}/end`);
                this.successMessage = `Épreuve "${race.name}" terminée`;
                this.loadRaces();
            } catch (error) {
                alert('Erreur lors de l\'arrêt : ' + (error.response?.data?.message || error.message));
            }
        },

        async deleteRace(race) {
            if (!confirm(`Êtes-vous sûr de vouloir supprimer l'épreuve "${race.name}" ?`)) return;

            try {
                await axios.delete(`/races/${race.id}`);
                this.successMessage = `Épreuve "${race.name}" supprimée`;
                this.loadRaces();
            } catch (error) {
                alert('Erreur lors de la suppression : ' + (error.response?.data?.message || error.message));
            }
        },

        async updateRaceOrder(race, newOrder) {
            if (!newOrder || newOrder < 1) return;

            try {
                await axios.post('/races/update-order', {
                    orders: [
                        { id: race.id, display_order: parseInt(newOrder) }
                    ]
                });

                race.display_order = parseInt(newOrder);
                this.successMessage = `Ordre du parcours "${race.name}" mis à jour`;

                setTimeout(() => {
                    this.successMessage = null;
                }, 2000);
            } catch (error) {
                alert('Erreur lors de la mise à jour de l\'ordre : ' + (error.response?.data?.message || error.message));
            }
        }
    }
}
</script>
@endsection
