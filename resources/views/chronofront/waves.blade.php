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
                                        <button class="btn btn-outline-primary" @click="openEditModal(wave)" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" @click="deleteWave(wave)" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>

                                    <!-- TOP Départ Configuration -->
                                    <div class="mt-2 p-2 border rounded" style="background: #f8f9fa;">
                                        <div class="small mb-2">
                                            <strong>Configuration TOP départ</strong>
                                        </div>

                                        <!-- Fenêtre de détection (Mode 2: single_reader_waves) -->
                                        <div class="mb-2">
                                            <label class="form-label small mb-1">Fenêtre détection (min)</label>
                                            <input
                                                type="number"
                                                class="form-control form-control-sm"
                                                :value="wave.depart_window_minutes || 5"
                                                @change="updateWindow(wave, $event.target.value)"
                                                min="1"
                                                max="60"
                                                title="Fenêtre de détection DÉPART (±X minutes autour du TOP) - Utilisée en mode single_reader_waves"
                                            >
                                            <div class="form-text" style="font-size: 0.7rem;">
                                                Utilisée uniquement en mode <em>single_reader_waves</em>
                                            </div>
                                        </div>

                                        <!-- Heure réelle (éditable) -->
                                        <div class="mb-2" x-show="wave.real_start_time">
                                            <label class="form-label small mb-1">Heure réelle</label>
                                            <input
                                                type="datetime-local"
                                                class="form-control form-control-sm"
                                                :value="wave.real_start_time ? wave.real_start_time.slice(0,16) : ''"
                                                @change="updateRealStartTime(wave, $event.target.value)"
                                                step="1"
                                                title="Modifier manuellement l'heure de départ"
                                            >
                                        </div>

                                        <!-- Bouton TOP Départ -->
                                        <button
                                            class="btn btn-sm w-100"
                                            :class="wave.real_start_time ? 'btn-success' : 'btn-primary'"
                                            @click="topDepart(wave)"
                                            title="Enregistrer le TOP départ maintenant"
                                        >
                                            <i class="bi bi-stopwatch"></i>
                                            <span x-show="!wave.real_start_time">TOP départ (NOW)</span>
                                            <span x-show="wave.real_start_time">✓ TOP enregistré</span>
                                        </button>

                                        <!-- Info fenêtre calculée -->
                                        <div class="small text-muted mt-1" x-show="wave.real_start_time" style="font-size: 0.75rem;">
                                            <span x-text="getWindowInfo(wave)"></span>
                                        </div>
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

        async topDepart(wave) {
            const confirmMsg = wave.real_start_time
                ? `Enregistrer un nouveau TOP départ pour "${wave.name}" ?\n\nCela va écraser le TOP départ précédent.`
                : `Enregistrer le TOP départ pour "${wave.name}" ?\n\nCela va activer la fenêtre de détection DEPART basée sur l'heure réelle.`;

            if (!confirm(confirmMsg)) return;

            try {
                const response = await axios.post(`/api/waves/${wave.id}/top-depart`);
                const data = response.data;

                this.successMessage = `TOP départ enregistré pour "${wave.name}"`;
                this.loadWaves();

                // Format dates for display (ISO 8601 to local time)
                const formatDateTime = (isoString) => {
                    const date = new Date(isoString);
                    return date.toLocaleString('fr-FR', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                };

                const formatTime = (isoString) => {
                    const date = new Date(isoString);
                    return date.toLocaleTimeString('fr-FR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                };

                // Show window info
                alert(`✅ TOP départ enregistré !\n\n` +
                      `Vague: ${data.wave.name}\n` +
                      `Heure réelle: ${formatDateTime(data.wave.real_start_time)}\n` +
                      `Fenêtre DÉPART: ${formatTime(data.wave.depart_window_start)} → ${formatTime(data.wave.depart_window_end)}`);
            } catch (error) {
                alert('Erreur lors de l\'enregistrement du TOP départ : ' + (error.response?.data?.message || error.message));
            }
        },

        async updateWindow(wave, minutes) {
            const value = parseInt(minutes);
            if (value < 1 || value > 60) {
                alert('La fenêtre doit être entre 1 et 60 minutes');
                return;
            }

            try {
                await axios.patch(`/waves/${wave.id}`, {
                    depart_window_minutes: value
                });
                wave.depart_window_minutes = value;
                this.successMessage = `Fenêtre de détection mise à jour : ±${value} minutes`;
            } catch (error) {
                alert('Erreur lors de la modification : ' + (error.response?.data?.message || error.message));
            }
        },

        async updateRealStartTime(wave, datetime) {
            if (!datetime) return;

            const confirmMsg = `Modifier l'heure de départ pour "${wave.name}" ?\n\n` +
                `Nouvelle heure : ${new Date(datetime).toLocaleString('fr-FR')}\n\n` +
                `⚠️ ATTENTION : Cela va recalculer TOUS les résultats de cette vague !`;

            if (!confirm(confirmMsg)) {
                this.loadWaves(); // Reset input
                return;
            }

            try {
                const response = await axios.post(`/waves/${wave.id}/update-real-start-time`, {
                    real_start_time: datetime
                });

                this.successMessage = `Heure de départ mise à jour : ${response.data.reprocessed} détection(s) retraitée(s)`;
                this.loadWaves();

                alert(`✅ Heure mise à jour !\n\n` +
                      `Détections retraitées : ${response.data.reprocessed}\n` +
                      `Résultats recalculés : ${response.data.results_updated || 0}`);
            } catch (error) {
                alert('Erreur lors de la modification : ' + (error.response?.data?.message || error.message));
                this.loadWaves();
            }
        },

        getWindowInfo(wave) {
            if (!wave.real_start_time || !wave.depart_window_minutes) return '';

            const startTime = new Date(wave.real_start_time);
            const windowMin = wave.depart_window_minutes;

            const windowStart = new Date(startTime.getTime() - windowMin * 60000);
            const windowEnd = new Date(startTime.getTime() + windowMin * 60000);

            const formatTime = (d) => d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

            return `Fenêtre DÉPART: ${formatTime(windowStart)} → ${formatTime(windowEnd)}`;
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
        },

        formatTime(datetime) {
            if (!datetime) return 'N/A';
            const date = new Date(datetime);
            return date.toLocaleString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
    }
}
</script>
@endsection
