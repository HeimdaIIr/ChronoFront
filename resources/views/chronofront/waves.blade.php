@extends('chronofront.layout')

@section('title', 'Gestion des Vagues')

@section('content')
<div class="container-fluid" x-data="wavesManager()">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2"><i class="bi bi-flag text-primary"></i> Gestion des Vagues</h1>
            <p class="text-muted">Gérez les vagues de départ de vos épreuves</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" @click="openCreateModal">
                <i class="bi bi-plus-circle"></i> Nouvelle Vague
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
                    <select class="form-select" x-model="selectedEventFilter" @change="onEventChange">
                        <option value="">Tous les événements</option>
                        <template x-for="event in events" :key="event.id">
                            <option :value="event.id" x-text="event.name"></option>
                        </template>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Filtrer par épreuve</label>
                    <select class="form-select" x-model="selectedRaceFilter" @change="loadWaves">
                        <option value="">Toutes les épreuves</option>
                        <template x-for="race in filteredRaces" :key="race.id">
                            <option :value="race.id" x-text="race.name"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Waves Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div x-show="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>

            <div x-show="!loading && waves.length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3">Aucune vague trouvée</p>
            </div>

            <div x-show="!loading && waves.length > 0" class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>N° Vague</th>
                            <th>Nom</th>
                            <th>Épreuve (Parcours)</th>
                            <th>Événement</th>
                            <th>Heure de départ</th>
                            <th>Heure de fin</th>
                            <th>Participants</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="wave in waves" :key="wave.id">
                            <tr>
                                <td>
                                    <span class="badge bg-dark fs-6" x-text="'#' + (wave.wave_number || '-')"></span>
                                </td>
                                <td>
                                    <strong x-text="wave.name"></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info" x-text="wave.race?.name"></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary" x-text="wave.race?.event?.name"></span>
                                </td>
                                <td>
                                    <span x-text="formatDateTime(wave.start_time)"></span>
                                </td>
                                <td>
                                    <span x-text="formatDateTime(wave.end_time)"></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary" x-text="(wave.entrants?.length || 0) + ' participants'"></span>
                                </td>
                                <td>
                                    <span x-show="wave.is_started && !wave.end_time" class="badge bg-success">
                                        <i class="bi bi-play-fill"></i> En cours
                                    </span>
                                    <span x-show="wave.end_time" class="badge bg-secondary">
                                        <i class="bi bi-stop-fill"></i> Terminée
                                    </span>
                                    <span x-show="!wave.is_started" class="badge bg-warning">
                                        <i class="bi bi-clock"></i> Pas démarrée
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm mb-1">
                                        <button
                                            class="btn btn-success"
                                            @click="startWave(wave)"
                                            x-show="!wave.is_started"
                                            title="Démarrer"
                                        >
                                            <i class="bi bi-play-fill"></i>
                                        </button>
                                        <button
                                            class="btn btn-danger"
                                            @click="endWave(wave)"
                                            x-show="wave.is_started && !wave.end_time"
                                            title="Terminer"
                                        >
                                            <i class="bi bi-stop-fill"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" @click="openEditModal(wave)" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" @click="deleteWave(wave)" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <div>
                                        <button
                                            class="btn btn-warning btn-sm w-100"
                                            @click="assignAllEntrants(wave)"
                                            title="Assigner tous les participants de ce parcours à cette vague"
                                        >
                                            <i class="bi bi-people-fill"></i> Assigner participants
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="editingWave ? 'Modifier la vague' : 'Nouvelle vague'"></h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <form @submit.prevent="saveWave">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Événement <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="form.event_id" @change="onFormEventChange" required>
                                    <option value="">-- Sélectionnez --</option>
                                    <template x-for="event in events" :key="event.id">
                                        <option :value="event.id" x-text="event.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Épreuve (Parcours) <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="form.race_id" required>
                                    <option value="">-- Sélectionnez --</option>
                                    <template x-for="race in formFilteredRaces" :key="race.id">
                                        <option :value="race.id" x-text="race.name"></option>
                                    </template>
                                </select>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Tous les participants de cette vague seront classés dans cette épreuve
                                </div>
                            </div>

                            <div class="col-6">
                                <label class="form-label">Numéro de vague <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" x-model="form.wave_number" required min="1" placeholder="1, 2, 3...">
                                <div class="form-text">Ex: 1, 2, 3...</div>
                            </div>

                            <div class="col-6">
                                <label class="form-label">Nom de la vague <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.name" required placeholder="Ex: Elite, Débutants...">
                                <div class="form-text">Description libre</div>
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
function wavesManager() {
    return {
        waves: [],
        events: [],
        races: [],
        filteredRaces: [],
        formFilteredRaces: [],
        selectedEventFilter: '',
        selectedRaceFilter: '',
        loading: false,
        showModal: false,
        editingWave: null,
        saving: false,
        successMessage: null,
        form: {
            event_id: '',
            race_id: '',
            wave_number: '',
            name: ''
        },

        init() {
            this.loadEvents();
            this.loadRaces();
            this.loadWaves();
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
                this.filteredRaces = this.races;
                this.formFilteredRaces = this.races;
            } catch (error) {
                console.error('Erreur lors du chargement des épreuves', error);
            }
        },

        onEventChange() {
            if (this.selectedEventFilter) {
                this.filteredRaces = this.races.filter(race => race.event_id == this.selectedEventFilter);
                this.selectedRaceFilter = '';
            } else {
                this.filteredRaces = this.races;
            }
            this.loadWaves();
        },

        onFormEventChange() {
            if (this.form.event_id) {
                this.formFilteredRaces = this.races.filter(race => race.event_id == this.form.event_id);
                this.form.race_id = '';
            } else {
                this.formFilteredRaces = this.races;
            }
        },

        async loadWaves() {
            this.loading = true;
            try {
                let url = '/waves';
                if (this.selectedRaceFilter) {
                    url += `/race/${this.selectedRaceFilter}`;
                }
                const response = await axios.get(url);

                // Filter by event if selected but no race filter
                if (this.selectedEventFilter && !this.selectedRaceFilter) {
                    const raceIds = this.filteredRaces.map(r => r.id);
                    this.waves = response.data.filter(w => raceIds.includes(w.race_id));
                } else {
                    this.waves = response.data;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des vagues', error);
            } finally {
                this.loading = false;
            }
        },

        openCreateModal() {
            this.editingWave = null;
            this.form = {
                event_id: this.selectedEventFilter || '',
                race_id: this.selectedRaceFilter || '',
                wave_number: '',
                name: ''
            };
            if (this.form.event_id) {
                this.formFilteredRaces = this.races.filter(race => race.event_id == this.form.event_id);
            } else {
                this.formFilteredRaces = this.races;
            }
            this.showModal = true;
        },

        openEditModal(wave) {
            this.editingWave = wave;
            this.form = {
                event_id: wave.race?.event_id || '',
                race_id: wave.race_id,
                wave_number: wave.wave_number || '',
                name: wave.name
            };
            if (this.form.event_id) {
                this.formFilteredRaces = this.races.filter(race => race.event_id == this.form.event_id);
            } else {
                this.formFilteredRaces = this.races;
            }
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingWave = null;
        },

        async saveWave() {
            this.saving = true;
            try {
                if (this.editingWave) {
                    await axios.put(`/waves/${this.editingWave.id}`, {
                        wave_number: parseInt(this.form.wave_number),
                        name: this.form.name
                    });
                    this.successMessage = 'Vague modifiée avec succès';
                } else {
                    await axios.post('/waves', {
                        race_id: this.form.race_id,
                        wave_number: parseInt(this.form.wave_number),
                        name: this.form.name
                    });
                    this.successMessage = 'Vague créée avec succès';
                }
                this.closeModal();
                this.loadWaves();
            } catch (error) {
                alert('Erreur lors de l\'enregistrement : ' + (error.response?.data?.message || error.message));
            } finally {
                this.saving = false;
            }
        },

        async startWave(wave) {
            if (!confirm(`Démarrer la vague "${wave.name}" ?`)) return;

            try {
                await axios.post(`/waves/${wave.id}/start`);
                this.successMessage = `Vague "${wave.name}" démarrée`;
                this.loadWaves();
            } catch (error) {
                alert('Erreur lors du démarrage : ' + (error.response?.data?.message || error.message));
            }
        },

        async endWave(wave) {
            if (!confirm(`Terminer la vague "${wave.name}" ?`)) return;

            try {
                await axios.post(`/waves/${wave.id}/end`);
                this.successMessage = `Vague "${wave.name}" terminée`;
                this.loadWaves();
            } catch (error) {
                alert('Erreur lors de l\'arrêt : ' + (error.response?.data?.message || error.message));
            }
        },

        async deleteWave(wave) {
            if (!confirm(`Êtes-vous sûr de vouloir supprimer la vague "${wave.name}" ?\n\nATTENTION : Les participants de cette vague seront également supprimés.`)) return;

            try {
                await axios.delete(`/waves/${wave.id}`);
                this.successMessage = `Vague "${wave.name}" supprimée`;
                this.loadWaves();
            } catch (error) {
                alert('Erreur lors de la suppression : ' + (error.response?.data?.message || error.message));
            }
        },

        async assignAllEntrants(wave) {
            const raceName = wave.race?.name || 'ce parcours';
            if (!confirm(`Assigner TOUS les participants de l'épreuve "${raceName}" (qui n'ont pas encore de vague) à la vague "${wave.name}" ?\n\nCette action ne peut pas être annulée.`)) return;

            try {
                const response = await axios.post(`/waves/${wave.id}/assign-all`);
                this.successMessage = response.data.message;

                // Afficher info sur participants déjà assignés ailleurs
                if (response.data.already_assigned_elsewhere > 0) {
                    this.successMessage += `\n\nNote : ${response.data.already_assigned_elsewhere} participant(s) étaient déjà assignés à d'autres vagues.`;
                }

                this.loadWaves();
            } catch (error) {
                alert('Erreur lors de l\'assignation : ' + (error.response?.data?.message || error.message));
            }
        },

        formatDateTime(datetime) {
            if (!datetime) return 'N/A';
            const date = new Date(datetime);
            return date.toLocaleString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
    }
}
</script>
@endsection
