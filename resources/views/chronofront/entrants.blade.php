@extends('chronofront.layout')

@section('title', 'Gestion des Participants')

@section('content')
<div class="container-fluid" x-data="entrantsManager()">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2"><i class="bi bi-people text-info"></i> Gestion des Participants</h1>
            <p class="text-muted">Gérez les participants de vos événements</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('entrants.import') }}" class="btn btn-success me-2">
                <i class="bi bi-upload"></i> Import CSV
            </a>
            <button class="btn btn-primary" @click="openCreateModal">
                <i class="bi bi-plus-circle"></i> Nouveau Participant
            </button>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle-fill"></i>
        <strong>Attribution des parcours :</strong> Les participants importés via CSV sont automatiquement affectés à leur parcours. Vous pouvez modifier le parcours d'un participant en cliquant sur <i class="bi bi-pencil"></i> (utile pour les changements de dernière minute).
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                <div class="col-md-4">
                    <label class="form-label">Événement</label>
                    <select class="form-select" x-model="selectedEventFilter" @change="loadRacesByEvent">
                        <option value="">Tous les événements</option>
                        <template x-for="event in events" :key="event.id">
                            <option :value="event.id" x-text="event.name"></option>
                        </template>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Épreuve</label>
                    <select class="form-select" x-model="selectedRaceFilter" @change="loadEntrants">
                        <option value="">Toutes les épreuves</option>
                        <template x-for="race in filteredRaces" :key="race.id">
                            <option :value="race.id" x-text="race.name"></option>
                        </template>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Recherche</label>
                    <input type="text" class="form-control" x-model="searchQuery" @input="filterEntrants" placeholder="Nom, dossard, RFID...">
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3 x-text="filteredEntrants.length"></h3>
                    <p class="mb-0">Participants affichés</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h3 x-text="entrants.length"></h3>
                    <p class="mb-0">Total participants</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Entrants Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div x-show="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>

            <div x-show="!loading && filteredEntrants.length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3">Aucun participant trouvé</p>
            </div>

            <div x-show="!loading && filteredEntrants.length > 0" class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Dossard</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Sexe</th>
                            <th>Catégorie</th>
                            <th>Épreuve</th>
                            <th>Vague</th>
                            <th>Tag RFID</th>
                            <th>Club</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="entrant in paginatedEntrants" :key="entrant.id">
                            <tr>
                                <td><strong x-text="entrant.bib_number"></strong></td>
                                <td x-text="entrant.lastname"></td>
                                <td x-text="entrant.firstname"></td>
                                <td>
                                    <span class="badge" :class="entrant.gender === 'M' ? 'bg-primary' : 'bg-danger'" x-text="entrant.gender"></span>
                                </td>
                                <td>
                                    <span class="badge bg-info" x-text="entrant.category?.name || 'N/A'"></span>
                                </td>
                                <td>
                                    <span x-show="entrant.race?.display_order" class="badge bg-dark me-1" x-text="'#' + entrant.race?.display_order"></span>
                                    <span class="badge bg-secondary" x-text="entrant.race?.name || 'N/A'"></span>
                                </td>
                                <td>
                                    <span class="badge bg-warning" x-text="entrant.wave?.name || 'N/A'"></span>
                                </td>
                                <td><code x-text="entrant.rfid_tag || 'N/A'"></code></td>
                                <td x-text="entrant.club || '-'"></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" @click="openEditModal(entrant)" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" @click="deleteEntrant(entrant)" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Affichage <span x-text="(currentPage - 1) * perPage + 1"></span>
                        à <span x-text="Math.min(currentPage * perPage, filteredEntrants.length)"></span>
                        sur <span x-text="filteredEntrants.length"></span>
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item" :class="{'disabled': currentPage === 1}">
                                <button class="page-link" @click="currentPage--" :disabled="currentPage === 1">Précédent</button>
                            </li>
                            <template x-for="page in totalPages" :key="page">
                                <li class="page-item" :class="{'active': currentPage === page}">
                                    <button class="page-link" @click="currentPage = page" x-text="page"></button>
                                </li>
                            </template>
                            <li class="page-item" :class="{'disabled': currentPage === totalPages}">
                                <button class="page-link" @click="currentPage++" :disabled="currentPage === totalPages">Suivant</button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal fade" :class="{'show d-block': showModal}" tabindex="-1" style="background: rgba(0,0,0,0.5);" x-show="showModal" @click.self="closeModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="editingEntrant ? 'Modifier le participant' : 'Nouveau participant'"></h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <form @submit.prevent="saveEntrant">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.lastname" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.firstname" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Sexe <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="form.gender" required>
                                    <option value="M">Homme</option>
                                    <option value="F">Femme</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" x-model="form.birth_date">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Dossard</label>
                                <input type="text" class="form-control" x-model="form.bib_number">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Épreuve / Parcours
                                    <span class="text-muted small">(modifiable)</span>
                                </label>
                                <select class="form-select" x-model="form.race_id">
                                    <option value="">-- Sélectionnez --</option>
                                    <template x-for="race in sortedRaces" :key="race.id">
                                        <option :value="race.id">
                                            <span x-show="race.display_order" x-text="'#' + race.display_order + ' - '"></span>
                                            <span x-text="race.name"></span>
                                        </option>
                                    </template>
                                </select>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Vous pouvez changer le parcours d'un participant ici
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vague</label>
                                <select class="form-select" x-model="form.wave_id">
                                    <option value="">-- Sélectionnez --</option>
                                    <template x-for="wave in waves" :key="wave.id">
                                        <option :value="wave.id" x-text="wave.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" x-model="form.email">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" x-model="form.phone">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Club</label>
                                <input type="text" class="form-control" x-model="form.club">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Équipe</label>
                                <input type="text" class="form-control" x-model="form.team">
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
function entrantsManager() {
    return {
        entrants: [],
        filteredEntrants: [],
        events: [],
        races: [],
        filteredRaces: [],
        waves: [],
        selectedEventFilter: '',
        selectedRaceFilter: '',
        searchQuery: '',
        loading: false,
        showModal: false,
        editingEntrant: null,
        saving: false,
        successMessage: null,
        currentPage: 1,
        perPage: 50,
        form: {
            firstname: '',
            lastname: '',
            gender: 'M',
            birth_date: null,
            bib_number: '',
            race_id: '',
            wave_id: '',
            email: '',
            phone: '',
            club: '',
            team: ''
        },

        get paginatedEntrants() {
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            return this.filteredEntrants.slice(start, end);
        },

        get totalPages() {
            return Math.ceil(this.filteredEntrants.length / this.perPage);
        },

        get sortedRaces() {
            return [...this.races].sort((a, b) => {
                if (a.display_order && b.display_order) {
                    return a.display_order - b.display_order;
                } else if (a.display_order) {
                    return -1;
                } else if (b.display_order) {
                    return 1;
                }
                return 0;
            });
        },

        init() {
            this.loadEvents();
            this.loadRaces();
            this.loadWaves();
            this.loadEntrants();
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
                this.filteredRaces = response.data;
            } catch (error) {
                console.error('Erreur lors du chargement des épreuves', error);
            }
        },

        async loadRacesByEvent() {
            if (this.selectedEventFilter) {
                try {
                    const response = await axios.get(`/races/event/${this.selectedEventFilter}`);
                    this.filteredRaces = response.data;
                } catch (error) {
                    console.error('Erreur', error);
                }
            } else {
                this.filteredRaces = this.races;
            }
            this.selectedRaceFilter = '';
            this.loadEntrants();
        },

        async loadWaves() {
            try {
                const response = await axios.get('/waves');
                this.waves = response.data;
            } catch (error) {
                console.error('Erreur lors du chargement des vagues', error);
            }
        },

        async loadEntrants() {
            this.loading = true;
            try {
                let url = '/entrants';
                const params = new URLSearchParams();
                if (this.selectedRaceFilter) {
                    params.append('race_id', this.selectedRaceFilter);
                }
                if (params.toString()) {
                    url += '?' + params.toString();
                }
                const response = await axios.get(url);

                // Si un événement est sélectionné (mais pas d'épreuve spécifique),
                // filtrer par les races de cet événement
                if (this.selectedEventFilter && !this.selectedRaceFilter) {
                    const raceIds = this.filteredRaces.map(r => r.id);
                    this.entrants = response.data.filter(e => raceIds.includes(e.race_id));
                } else {
                    this.entrants = response.data;
                }

                this.filterEntrants();
            } catch (error) {
                console.error('Erreur lors du chargement des participants', error);
            } finally {
                this.loading = false;
            }
        },

        filterEntrants() {
            if (!this.searchQuery) {
                this.filteredEntrants = this.entrants;
            } else {
                const query = this.searchQuery.toLowerCase();
                this.filteredEntrants = this.entrants.filter(entrant =>
                    entrant.firstname?.toLowerCase().includes(query) ||
                    entrant.lastname?.toLowerCase().includes(query) ||
                    entrant.bib_number?.toString().includes(query) ||
                    entrant.rfid_tag?.toLowerCase().includes(query)
                );
            }
            this.currentPage = 1;
        },

        openCreateModal() {
            this.editingEntrant = null;
            this.form = {
                firstname: '',
                lastname: '',
                gender: 'M',
                birth_date: null,
                bib_number: '',
                race_id: this.selectedRaceFilter || '',
                wave_id: '',
                email: '',
                phone: '',
                club: '',
                team: ''
            };
            this.showModal = true;
        },

        openEditModal(entrant) {
            this.editingEntrant = entrant;
            this.form = {
                firstname: entrant.firstname,
                lastname: entrant.lastname,
                gender: entrant.gender,
                birth_date: entrant.birth_date,
                bib_number: entrant.bib_number,
                race_id: entrant.race_id,
                wave_id: entrant.wave_id,
                email: entrant.email || '',
                phone: entrant.phone || '',
                club: entrant.club || '',
                team: entrant.team || ''
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingEntrant = null;
        },

        async saveEntrant() {
            this.saving = true;
            try {
                if (this.editingEntrant) {
                    await axios.put(`/entrants/${this.editingEntrant.id}`, this.form);
                    this.successMessage = 'Participant modifié avec succès';
                } else {
                    await axios.post('/entrants', this.form);
                    this.successMessage = 'Participant créé avec succès';
                }
                this.closeModal();
                this.loadEntrants();
            } catch (error) {
                alert('Erreur lors de l\'enregistrement : ' + (error.response?.data?.message || error.message));
            } finally {
                this.saving = false;
            }
        },

        async deleteEntrant(entrant) {
            if (!confirm(`Êtes-vous sûr de vouloir supprimer ${entrant.firstname} ${entrant.lastname} ?`)) return;

            try {
                await axios.delete(`/entrants/${entrant.id}`);
                this.successMessage = 'Participant supprimé';
                this.loadEntrants();
            } catch (error) {
                alert('Erreur lors de la suppression : ' + (error.response?.data?.message || error.message));
            }
        }
    }
}
</script>
@endsection
