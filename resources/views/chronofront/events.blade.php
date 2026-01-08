@extends('chronofront.layout')

@section('title', 'Gestion des Événements')

@section('styles')
<style>
    .modal {
        display: none;
    }
    .modal.show.d-block {
        display: block !important;
    }
    .nav-tabs .nav-link {
        cursor: pointer;
        background: none;
        border: 1px solid transparent;
    }
    .nav-tabs .nav-link.active {
        color: #495057;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }
    .badge-sm {
        font-size: 0.75rem;
    }
</style>
@endsection

@section('content')
<div x-data="eventsManager()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="bi bi-calendar-event text-primary"></i> Gestion des Événements</h1>
            <p class="text-muted">Créez et gérez vos événements sportifs</p>
        </div>
        <button class="btn btn-primary" @click="showCreateModal = true">
            <i class="bi bi-plus-circle"></i> Nouvel événement
        </button>
    </div>

    <!-- Events List -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-list"></i> Liste des événements
        </div>
        <div class="card-body">
            <template x-if="loading">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </template>

            <template x-if="!loading && events.length === 0">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                    <p class="mt-3">Aucun événement créé</p>
                    <button class="btn btn-primary" @click="showCreateModal = true">
                        <i class="bi bi-plus-circle"></i> Créer votre premier événement
                    </button>
                </div>
            </template>

            <div class="table-responsive" x-show="!loading && events.length > 0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Lieu</th>
                            <th>Date début</th>
                            <th>Date fin</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="event in events" :key="event.id">
                            <tr>
                                <td>
                                    <strong x-text="event.name"></strong>
                                    <br>
                                    <small class="text-muted" x-text="event.description"></small>
                                </td>
                                <td x-text="event.location"></td>
                                <td x-text="formatDate(event.date_start)"></td>
                                <td x-text="formatDate(event.date_end)"></td>
                                <td>
                                    <span class="badge" :class="event.is_active ? 'bg-success' : 'bg-secondary'"
                                          x-text="event.is_active ? 'Actif' : 'Inactif'"></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info" @click="editEvent(event)" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" @click="viewRaces(event)" title="Épreuves">
                                            <i class="bi bi-trophy"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" @click="deleteEvent(event.id)" title="Supprimer">
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

    <!-- Create Event Modal -->
    <div class="modal" :class="{'show d-block': showCreateModal}" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nouvel événement</h5>
                    <button type="button" class="btn-close" @click="showCreateModal = false"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="createEvent">
                        <div class="mb-3">
                            <label class="form-label">Nom de l'événement *</label>
                            <input type="text" class="form-control" x-model="newEvent.name" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date début *</label>
                                <input type="datetime-local" class="form-control" x-model="newEvent.date_start" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date fin *</label>
                                <input type="datetime-local" class="form-control" x-model="newEvent.date_end" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lieu</label>
                            <input type="text" class="form-control" x-model="newEvent.location">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" x-model="newEvent.description"></textarea>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="isActive" x-model="newEvent.is_active">
                            <label class="form-check-label" for="isActive">Événement actif</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Annuler</button>
                    <button type="button" class="btn btn-primary" @click="createEvent">
                        <i class="bi bi-save"></i> Créer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div class="modal" :class="{'show d-block': showEditModal}" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Modifier l'événement</h5>
                    <button type="button" class="btn-close" @click="closeEditModal()"></button>
                </div>
                <div class="modal-body">
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <button class="nav-link" :class="{'active': editTab === 'info'}" @click="editTab = 'info'">
                                <i class="bi bi-info-circle"></i> Informations
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" :class="{'active': editTab === 'readers'}" @click="editTab = 'readers'; loadReaders()">
                                <i class="bi bi-broadcast"></i> Lecteurs RFID
                            </button>
                        </li>
                    </ul>

                    <!-- Tab: Event Information -->
                    <div x-show="editTab === 'info'">
                        <form @submit.prevent="updateEvent">
                            <div class="mb-3">
                                <label class="form-label">Nom de l'événement *</label>
                                <input type="text" class="form-control" x-model="editingEvent.name" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date début *</label>
                                    <input type="datetime-local" class="form-control" x-model="editingEvent.date_start" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date fin *</label>
                                    <input type="datetime-local" class="form-control" x-model="editingEvent.date_end" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Lieu</label>
                                <input type="text" class="form-control" x-model="editingEvent.location">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" rows="3" x-model="editingEvent.description"></textarea>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="editIsActive" x-model="editingEvent.is_active">
                                <label class="form-check-label" for="editIsActive">Événement actif</label>
                            </div>
                        </form>
                    </div>

                    <!-- Tab: RFID Readers -->
                    <div x-show="editTab === 'readers'">
                        <!-- Info Box -->
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle"></i>
                            <strong>Calcul automatique IP :</strong> ChronoFront supporte 3 types de réseaux :
                            <ul class="mb-0 mt-2">
                                <li><strong>Local</strong> (192.168.10.X) : <code>192.168.10.{150+XX}</code> où XX = 2 derniers chiffres du serial</li>
                                <li><strong>VPN ATS Sport</strong> (10.8.0.X) : <code>10.8.0.{serial}</code> - Exemple: Serial 120 → 10.8.0.120</li>
                                <li><strong>Custom</strong> : IP personnalisée saisie manuellement</li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0"><i class="bi bi-list"></i> Lecteurs configurés</h6>
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-2" @click="loadReaders()">
                                    <i class="bi bi-arrow-clockwise"></i> Actualiser
                                </button>
                                <button class="btn btn-sm btn-primary" @click="openReaderModal()">
                                    <i class="bi bi-plus-circle"></i> Ajouter un lecteur
                                </button>
                            </div>
                        </div>

                        <template x-if="loadingReaders">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </template>

                        <template x-if="!loadingReaders && readers.length === 0">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-broadcast-pin" style="font-size: 3rem;"></i>
                                <p class="mt-3">Aucun lecteur configuré pour cet événement</p>
                                <button class="btn btn-primary" @click="openReaderModal()">
                                    <i class="bi bi-plus-circle"></i> Ajouter votre premier lecteur
                                </button>
                            </div>
                        </template>

                        <div class="table-responsive" x-show="!loadingReaders && readers.length > 0">
                            <table class="table table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Série</th>
                                        <th>Réseau</th>
                                        <th>IP</th>
                                        <th>Localisation</th>
                                        <th>Distance (km)</th>
                                        <th>Anti-rebond (s)</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="reader in sortedReaders" :key="reader.id">
                                        <tr>
                                            <td><strong x-text="reader.serial"></strong></td>
                                            <td>
                                                <span class="badge badge-sm" :class="{
                                                    'bg-primary': reader.network_type === 'local',
                                                    'bg-success': reader.network_type === 'vpn',
                                                    'bg-warning': reader.network_type === 'custom'
                                                }" x-text="getNetworkTypeLabel(reader.network_type || 'local')"></span>
                                            </td>
                                            <td><code x-text="reader.calculated_ip || calculateReaderIP(reader)"></code></td>
                                            <td><span class="badge bg-secondary" x-text="reader.location || 'Non défini'"></span></td>
                                            <td x-text="reader.distance_from_start + ' km'"></td>
                                            <td x-text="reader.anti_rebounce_seconds || '3'"></td>
                                            <td>
                                                <template x-if="!reader.date_test">
                                                    <span class="badge bg-secondary badge-sm">Jamais connecté</span>
                                                </template>
                                                <template x-if="reader.date_test && reader.is_online">
                                                    <span class="badge bg-success badge-sm">
                                                        <i class="bi bi-check-circle"></i> En ligne
                                                    </span>
                                                </template>
                                                <template x-if="reader.date_test && !reader.is_online">
                                                    <span class="badge bg-danger badge-sm">
                                                        <i class="bi bi-x-circle"></i> Hors ligne
                                                    </span>
                                                </template>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" @click="editReader(reader)" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" @click="deleteReader(reader.id)" title="Supprimer">
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeEditModal()">Fermer</button>
                    <button type="button" class="btn btn-primary" x-show="editTab === 'info'" @click="updateEvent()">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reader Create/Edit Modal (nested within Edit Event Modal) -->
    <div class="modal" :class="{'show d-block': showReaderModal}" tabindex="-1" style="background: rgba(0,0,0,0.6); z-index: 1060;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi" :class="readerEditMode ? 'bi-pencil' : 'bi-plus-circle'"></i>
                        <span x-text="readerEditMode ? 'Modifier le lecteur' : 'Ajouter un lecteur'"></span>
                    </h5>
                    <button type="button" class="btn-close" @click="closeReaderModal()"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveReader">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Numéro de série *</label>
                                <input type="text" class="form-control" x-model="currentReader.serial"
                                       @input="updateCalculatedReaderIP()" required placeholder="Ex: 107, 112, 120">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Type de réseau *</label>
                                <select class="form-select" x-model="currentReader.network_type"
                                        @change="updateCalculatedReaderIP()" required>
                                    <option value="local">Local (192.168.10.X)</option>
                                    <option value="vpn">VPN ATS Sport (10.8.0.X)</option>
                                    <option value="custom">IP Personnalisée</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">IP Calculée</label>
                                <input type="text" class="form-control" :value="calculatedReaderIP" readonly
                                       :class="{'text-muted': currentReader.network_type !== 'custom'}">
                            </div>
                        </div>

                        <!-- Custom IP field -->
                        <div class="row" x-show="currentReader.network_type === 'custom'">
                            <div class="col-12 mb-3">
                                <label class="form-label">IP Personnalisée *</label>
                                <input type="text" class="form-control" x-model="currentReader.custom_ip"
                                       @input="updateCalculatedReaderIP()"
                                       :required="currentReader.network_type === 'custom'"
                                       placeholder="Ex: 10.8.0.120, 192.168.1.50">
                                <small class="text-muted">Saisissez l'adresse IP complète du lecteur</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Localisation *</label>
                                <input type="text" class="form-control" x-model="currentReader.location"
                                       required placeholder="Ex: DEPART, KM5, ARRIVEE">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Distance depuis départ (km) *</label>
                                <input type="number" step="0.01" class="form-control"
                                       x-model="currentReader.distance_from_start"
                                       required placeholder="Ex: 0, 5, 10, 21">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Anti-rebond (secondes)</label>
                                <input type="number" class="form-control" x-model="currentReader.anti_rebounce_seconds"
                                       placeholder="3">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parcours associé</label>
                                <select class="form-select" x-model="currentReader.race_id">
                                    <option value="">Aucun (tous les parcours)</option>
                                    <template x-for="race in races" :key="race.id">
                                        <option :value="race.id" x-text="race.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="readerActive" x-model="currentReader.is_active">
                            <label class="form-check-label" for="readerActive">Lecteur actif</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeReaderModal()">Annuler</button>
                    <button type="button" class="btn btn-primary" @click="saveReader()" :disabled="savingReader">
                        <i class="bi" :class="savingReader ? 'bi-hourglass-split' : 'bi-save'"></i>
                        <span x-text="savingReader ? 'Enregistrement...' : 'Enregistrer'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function eventsManager() {
    return {
        events: [],
        loading: true,
        showCreateModal: false,
        showEditModal: false,
        showReaderModal: false,
        editTab: 'info',
        newEvent: {
            name: '',
            date_start: '',
            date_end: '',
            location: '',
            description: '',
            is_active: true
        },
        editingEvent: {},

        // Reader management
        readers: [],
        races: [],
        loadingReaders: false,
        readerEditMode: false,
        savingReader: false,
        currentReader: {},
        calculatedReaderIP: '',

        init() {
            this.loadEvents();
        },

        async loadEvents() {
            this.loading = true;
            try {
                const response = await axios.get('/events');
                this.events = response.data;
            } catch (error) {
                console.error('Error loading events:', error);
                alert('Erreur lors du chargement des événements');
            } finally {
                this.loading = false;
            }
        },

        async createEvent() {
            try {
                await axios.post('/events', this.newEvent);
                this.showCreateModal = false;
                this.resetForm();
                await this.loadEvents();
                alert('Événement créé avec succès !');
            } catch (error) {
                console.error('Error creating event:', error);
                alert('Erreur lors de la création de l\'événement');
            }
        },

        editEvent(event) {
            this.editingEvent = { ...event };
            // Format dates for datetime-local input
            if (this.editingEvent.date_start) {
                this.editingEvent.date_start = this.formatDateTimeLocal(this.editingEvent.date_start);
            }
            if (this.editingEvent.date_end) {
                this.editingEvent.date_end = this.formatDateTimeLocal(this.editingEvent.date_end);
            }
            this.editTab = 'info';
            this.showEditModal = true;
        },

        async updateEvent() {
            try {
                await axios.put(`/events/${this.editingEvent.id}`, this.editingEvent);
                this.showEditModal = false;
                await this.loadEvents();
                alert('Événement modifié avec succès !');
            } catch (error) {
                console.error('Error updating event:', error);
                alert('Erreur lors de la modification de l\'événement');
            }
        },

        closeEditModal() {
            this.showEditModal = false;
            this.editingEvent = {};
            this.readers = [];
            this.races = [];
        },

        async deleteEvent(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) return;

            try {
                await axios.delete(`/events/${id}`);
                await this.loadEvents();
                alert('Événement supprimé avec succès !');
            } catch (error) {
                console.error('Error deleting event:', error);
                alert('Erreur lors de la suppression de l\'événement');
            }
        },

        viewRaces(event) {
            window.location.href = `{{ route('races') }}?event_id=${event.id}`;
        },

        resetForm() {
            this.newEvent = {
                name: '',
                date_start: '',
                date_end: '',
                location: '',
                description: '',
                is_active: true
            };
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        },

        formatDateTimeLocal(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        },

        // ============ READER MANAGEMENT ============

        async loadReaders() {
            if (!this.editingEvent.id) return;

            this.loadingReaders = true;
            try {
                const response = await axios.get(`/readers/event/${this.editingEvent.id}`);
                this.readers = response.data;
                await this.loadRaces();
            } catch (error) {
                console.error('Error loading readers:', error);
                alert('Erreur lors du chargement des lecteurs');
            } finally {
                this.loadingReaders = false;
            }
        },

        async loadRaces() {
            if (!this.editingEvent.id) return;

            try {
                const response = await axios.get(`/races/event/${this.editingEvent.id}`);
                this.races = response.data;
            } catch (error) {
                console.error('Error loading races:', error);
            }
        },

        get sortedReaders() {
            return [...this.readers].sort((a, b) => {
                return parseFloat(a.distance_from_start || 0) - parseFloat(b.distance_from_start || 0);
            });
        },

        calculateReaderIP(reader) {
            if (typeof reader === 'string' || typeof reader === 'number') {
                const serial = reader;
                if (!serial) return 'N/A';
                const lastTwoDigits = String(serial).slice(-2);
                const ipSuffix = 150 + parseInt(lastTwoDigits);
                return `192.168.10.${ipSuffix}`;
            }

            const networkType = reader.network_type || 'local';
            const serial = reader.serial;

            if (!serial) return 'N/A';

            switch (networkType) {
                case 'vpn':
                    return `10.8.0.${serial}`;
                case 'custom':
                    return reader.custom_ip || 'Non définie';
                case 'local':
                default:
                    const lastTwoDigits = String(serial).slice(-2);
                    const ipSuffix = 150 + parseInt(lastTwoDigits);
                    return `192.168.10.${ipSuffix}`;
            }
        },

        getNetworkTypeLabel(type) {
            const labels = {
                'local': 'Local',
                'vpn': 'VPN',
                'custom': 'Custom'
            };
            return labels[type] || type;
        },

        openReaderModal() {
            this.readerEditMode = false;
            this.currentReader = {
                serial: '',
                network_type: 'local',
                custom_ip: '',
                location: '',
                distance_from_start: 0,
                anti_rebounce_seconds: 3,
                event_id: this.editingEvent.id,
                race_id: '',
                is_active: true
            };
            this.calculatedReaderIP = '';
            this.showReaderModal = true;
        },

        editReader(reader) {
            this.readerEditMode = true;
            this.currentReader = { ...reader };
            this.updateCalculatedReaderIP();
            this.showReaderModal = true;
        },

        closeReaderModal() {
            this.showReaderModal = false;
            this.currentReader = {};
            this.calculatedReaderIP = '';
        },

        updateCalculatedReaderIP() {
            this.calculatedReaderIP = this.calculateReaderIP(this.currentReader);
        },

        async saveReader() {
            this.savingReader = true;
            try {
                if (this.readerEditMode) {
                    await axios.put(`/readers/${this.currentReader.id}`, this.currentReader);
                } else {
                    await axios.post('/readers', this.currentReader);
                }
                this.closeReaderModal();
                await this.loadReaders();
                alert('Lecteur enregistré avec succès !');
            } catch (error) {
                console.error('Error saving reader:', error);
                alert('Erreur lors de l\'enregistrement du lecteur');
            } finally {
                this.savingReader = false;
            }
        },

        async deleteReader(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce lecteur ?')) return;

            try {
                await axios.delete(`/readers/${id}`);
                await this.loadReaders();
                alert('Lecteur supprimé avec succès !');
            } catch (error) {
                console.error('Error deleting reader:', error);
                alert('Erreur lors de la suppression du lecteur');
            }
        }
    }
}
</script>
@endsection
