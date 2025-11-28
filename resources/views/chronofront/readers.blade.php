@extends('chronofront.layout')

@section('title', 'Configuration des Lecteurs RFID')

@section('content')
<div x-data="readersManager({{ $eventId }})">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="bi bi-broadcast text-primary"></i> Configuration des Lecteurs RFID</h1>
            <p class="text-muted">Configurez vos lecteurs Raspberry Pi pour l'événement</p>
        </div>
        <div>
            <button class="btn btn-outline-secondary me-2" @click="window.history.back()">
                <i class="bi bi-arrow-left"></i> Retour
            </button>
            <button class="btn btn-primary" @click="openCreateModal()">
                <i class="bi bi-plus-circle"></i> Ajouter un lecteur
            </button>
        </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle"></i>
        <strong>Calcul automatique IP :</strong> L'adresse IP est calculée selon la formule
        <code>192.168.10.1(50+XX)</code> où XX = les 2 derniers chiffres du numéro de série.
        <br>
        <small>Exemple : Serial 107 → IP 192.168.10.157 | Serial 112 → IP 192.168.10.162</small>
    </div>

    <!-- Readers List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list"></i> Lecteurs configurés</span>
            <button class="btn btn-sm btn-outline-primary" @click="loadReaders()">
                <i class="bi bi-arrow-clockwise"></i> Actualiser
            </button>
        </div>
        <div class="card-body">
            <template x-if="loading">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </template>

            <template x-if="!loading && readers.length === 0">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-broadcast-pin" style="font-size: 4rem;"></i>
                    <p class="mt-3">Aucun lecteur configuré pour cet événement</p>
                    <button class="btn btn-primary" @click="openCreateModal()">
                        <i class="bi bi-plus-circle"></i> Ajouter votre premier lecteur
                    </button>
                </div>
            </template>

            <div class="table-responsive" x-show="!loading && readers.length > 0">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Numéro Série</th>
                            <th>IP Calculée</th>
                            <th>Localisation</th>
                            <th>Distance (km)</th>
                            <th>Ordre</th>
                            <th>Anti-rebond (s)</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="reader in sortedReaders" :key="reader.id">
                            <tr>
                                <td>
                                    <strong x-text="reader.serial"></strong>
                                </td>
                                <td>
                                    <code x-text="calculateIP(reader.serial)"></code>
                                </td>
                                <td>
                                    <span class="badge bg-secondary" x-text="reader.location || 'Non défini'"></span>
                                </td>
                                <td x-text="reader.distance_from_start + ' km'"></td>
                                <td>
                                    <span class="badge bg-info" x-show="reader.checkpoint_order"
                                          x-text="'#' + reader.checkpoint_order"></span>
                                    <span class="text-muted" x-show="!reader.checkpoint_order">-</span>
                                </td>
                                <td x-text="reader.anti_rebounce_seconds || '3'"></td>
                                <td>
                                    <template x-if="!reader.date_test">
                                        <span class="badge bg-secondary">Jamais connecté</span>
                                    </template>
                                    <template x-if="reader.date_test && reader.is_online">
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> En ligne
                                        </span>
                                    </template>
                                    <template x-if="reader.date_test && !reader.is_online">
                                        <span class="badge bg-danger" :title="'Dernière connexion: ' + reader.last_seen">
                                            <i class="bi bi-x-circle"></i> Hors ligne
                                        </span>
                                    </template>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info" @click="pingReader(reader)"
                                                title="Tester la connexion" :disabled="pinging === reader.id">
                                            <i class="bi" :class="pinging === reader.id ? 'bi-hourglass-split' : 'bi-broadcast'"></i>
                                        </button>
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

    <!-- Create/Edit Reader Modal -->
    <div class="modal" :class="{'show d-block': showModal}" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi" :class="editMode ? 'bi-pencil' : 'bi-plus-circle'"></i>
                        <span x-text="editMode ? 'Modifier le lecteur' : 'Ajouter un lecteur'"></span>
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal()"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveReader">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Numéro de série *</label>
                                <input type="text" class="form-control" x-model="currentReader.serial"
                                       @input="updateCalculatedIP()" required
                                       placeholder="Ex: 107, 112">
                                <small class="text-muted">Les 2 derniers chiffres déterminent l'IP</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">IP Calculée (auto)</label>
                                <input type="text" class="form-control" :value="calculatedIP" readonly disabled>
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
                                       @input="updateCheckpointOrder()" required
                                       placeholder="Ex: 0, 5, 10, 21">
                                <small class="text-muted">Utilisé pour calculer l'ordre automatiquement</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Anti-rebond (secondes)</label>
                                <input type="number" class="form-control" x-model="currentReader.anti_rebounce_seconds"
                                       placeholder="3">
                                <small class="text-muted">Temps minimum entre 2 lectures du même dossard</small>
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
                            <input type="checkbox" class="form-check-input" id="isActive" x-model="currentReader.is_active">
                            <label class="form-check-label" for="isActive">Lecteur actif</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal()">Annuler</button>
                    <button type="button" class="btn btn-primary" @click="saveReader()" :disabled="saving">
                        <i class="bi" :class="saving ? 'bi-hourglass-split' : 'bi-save'"></i>
                        <span x-text="saving ? 'Enregistrement...' : 'Enregistrer'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function readersManager(eventId) {
    return {
        eventId: eventId,
        readers: [],
        races: [],
        loading: true,
        showModal: false,
        editMode: false,
        saving: false,
        pinging: null,
        currentReader: {},
        calculatedIP: '',

        init() {
            this.loadReaders();
            this.loadRaces();
        },

        async loadReaders() {
            this.loading = true;
            try {
                const response = await axios.get(`/api/readers/event/${this.eventId}`);
                this.readers = response.data;
            } catch (error) {
                console.error('Error loading readers:', error);
                alert('Erreur lors du chargement des lecteurs');
            } finally {
                this.loading = false;
            }
        },

        async loadRaces() {
            try {
                const response = await axios.get(`/api/races/event/${this.eventId}`);
                this.races = response.data;
            } catch (error) {
                console.error('Error loading races:', error);
            }
        },

        get sortedReaders() {
            return [...this.readers].sort((a, b) => {
                // Sort by distance_from_start
                return parseFloat(a.distance_from_start || 0) - parseFloat(b.distance_from_start || 0);
            });
        },

        calculateIP(serial) {
            if (!serial) return 'N/A';
            // Extract last 2 digits
            const lastTwoDigits = String(serial).slice(-2);
            const ipSuffix = 150 + parseInt(lastTwoDigits);
            return `192.168.10.${ipSuffix}`;
        },

        openCreateModal() {
            this.editMode = false;
            this.currentReader = {
                serial: '',
                location: '',
                distance_from_start: 0,
                anti_rebounce_seconds: 3,
                event_id: this.eventId,
                race_id: '',
                is_active: true
            };
            this.calculatedIP = '';
            this.showModal = true;
        },

        editReader(reader) {
            this.editMode = true;
            this.currentReader = { ...reader };
            this.updateCalculatedIP();
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.currentReader = {};
            this.calculatedIP = '';
        },

        updateCalculatedIP() {
            this.calculatedIP = this.calculateIP(this.currentReader.serial);
        },

        updateCheckpointOrder() {
            // Checkpoint order is calculated server-side based on distance
            // This is just for UI feedback
        },

        async saveReader() {
            this.saving = true;
            try {
                if (this.editMode) {
                    await axios.put(`/api/readers/${this.currentReader.id}`, this.currentReader);
                } else {
                    await axios.post('/api/readers', this.currentReader);
                }
                this.closeModal();
                await this.loadReaders();
                alert('Lecteur enregistré avec succès !');
            } catch (error) {
                console.error('Error saving reader:', error);
                alert('Erreur lors de l\'enregistrement du lecteur');
            } finally {
                this.saving = false;
            }
        },

        async deleteReader(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce lecteur ?')) return;

            try {
                await axios.delete(`/api/readers/${id}`);
                await this.loadReaders();
                alert('Lecteur supprimé avec succès !');
            } catch (error) {
                console.error('Error deleting reader:', error);
                alert('Erreur lors de la suppression du lecteur');
            }
        },

        async pingReader(reader) {
            this.pinging = reader.id;
            try {
                const ip = this.calculateIP(reader.serial);
                // In reality, the ping would be done server-side or by the Raspberry itself
                // For now, we just reload to check the date_test status
                alert(`Test de connexion pour ${reader.location} (${ip})...\nVérifiez que le lecteur envoie des données.`);
                setTimeout(() => {
                    this.loadReaders();
                }, 2000);
            } catch (error) {
                console.error('Error pinging reader:', error);
                alert('Erreur lors du test de connexion');
            } finally {
                setTimeout(() => {
                    this.pinging = null;
                }, 2000);
            }
        }
    }
}
</script>
@endsection
