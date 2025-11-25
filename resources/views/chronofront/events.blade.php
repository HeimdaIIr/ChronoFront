@extends('chronofront.layout')

@section('title', 'Gestion des Événements')

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
</div>
@endsection

@section('scripts')
<script>
function eventsManager() {
    return {
        events: [],
        loading: true,
        showCreateModal: false,
        newEvent: {
            name: '',
            date_start: '',
            date_end: '',
            location: '',
            description: '',
            is_active: true
        },

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
        }
    }
}
</script>
@endsection
