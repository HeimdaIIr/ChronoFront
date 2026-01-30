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
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nouvel événement</h5>
                    <button type="button" class="btn-close" @click="closeCreateModal()"></button>
                </div>
                <div class="modal-body">
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <button class="nav-link" :class="{'active': createTab === 'info'}" @click="createTab = 'info'">
                                <i class="bi bi-info-circle"></i> Informations
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" :class="{'active': createTab === 'readers'}" @click="createTab = 'readers'; loadCreateReaders()">
                                <i class="bi bi-broadcast"></i> Lecteurs RFID
                            </button>
                        </li>
                    </ul>

                    <!-- Tab: Event Information -->
                    <div x-show="createTab === 'info'">
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

                    <!-- Tab: RFID Readers -->
                    <div x-show="createTab === 'readers'">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0"><i class="bi bi-list"></i> Lecteurs configurés</h6>
                            <div x-show="tempEventId">
                                <button class="btn btn-sm btn-outline-primary me-2" @click="loadCreateReaders()">
                                    <i class="bi bi-arrow-clockwise"></i> Actualiser
                                </button>
                                <button class="btn btn-sm btn-primary" @click="openCreateReaderModal()">
                                    <i class="bi bi-plus-circle"></i> Ajouter un lecteur
                                </button>
                            </div>
                        </div>

                        <template x-if="loadingCreateReaders">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </template>

                        <template x-if="!loadingCreateReaders && !tempEventId">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-info-circle" style="font-size: 3rem;"></i>
                                <p class="mt-3">Créez d'abord l'événement dans l'onglet "Informations" pour pouvoir configurer les lecteurs RFID.</p>
                                <button class="btn btn-primary" @click="createTab = 'info'">
                                    <i class="bi bi-arrow-left"></i> Retour à l'onglet Informations
                                </button>
                            </div>
                        </template>

                        <template x-if="!loadingCreateReaders && tempEventId && createReaders.length === 0">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-broadcast-pin" style="font-size: 3rem;"></i>
                                <p class="mt-3">Aucun lecteur configuré pour cet événement</p>
                                <button class="btn btn-primary" @click="openCreateReaderModal()">
                                    <i class="bi bi-plus-circle"></i> Ajouter votre premier lecteur
                                </button>
                            </div>
                        </template>

                        <div class="table-responsive" x-show="!loadingCreateReaders && createReaders.length > 0">
                            <table class="table table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Série</th>
                                        <th>Localisation</th>
                                        <th>Mode</th>
                                        <th>Distance (km)</th>
                                        <th>Plages horaires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="reader in sortedCreateReaders" :key="reader.id">
                                        <tr>
                                            <td><strong x-text="reader.serial"></strong></td>
                                            <td><span class="badge bg-secondary" x-text="reader.location || 'Non défini'"></span></td>
                                            <td>
                                                <span class="badge badge-sm" :class="{
                                                    'bg-info': reader.mode === 'single_reader_simple',
                                                    'bg-primary': reader.mode === 'single_reader_waves',
                                                    'bg-success': reader.mode === 'multi_reader',
                                                    'bg-warning': reader.mode === 'multi_reader_waves'
                                                }" x-text="getModeLabel(reader.mode || 'multi_reader')" style="font-size: 0.7rem;"></span>
                                            </td>
                                            <td x-text="reader.distance_from_start + ' km'"></td>
                                            <td>
                                                <template x-if="reader.depart_time_start || reader.arrival_time_start">
                                                    <div style="font-size: 0.85rem;">
                                                        <div x-show="reader.depart_time_start" class="text-success">
                                                            <i class="bi bi-flag"></i> DEPART:
                                                            <strong x-text="reader.depart_time_start?.substring(0,5)"></strong> -
                                                            <strong x-text="reader.depart_time_end?.substring(0,5)"></strong>
                                                        </div>
                                                        <div x-show="reader.arrival_time_start" class="text-primary">
                                                            <i class="bi bi-flag-fill"></i> ARRIVEE:
                                                            <strong x-text="reader.arrival_time_start?.substring(0,5)"></strong>
                                                            <span x-show="reader.arrival_time_end">
                                                                - <strong x-text="reader.arrival_time_end?.substring(0,5)"></strong>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="!reader.depart_time_start && !reader.arrival_time_start">
                                                    <span class="text-muted small">-</span>
                                                </template>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" @click="editCreateReader(reader)" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" @click="deleteCreateReader(reader.id)" title="Supprimer">
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
                    <button type="button" class="btn btn-secondary" @click="closeCreateModal()">Fermer</button>
                    <button type="button" class="btn btn-primary" x-show="createTab === 'info' && !tempEventId" @click="createEvent()">
                        <i class="bi bi-save"></i> Créer l'événement
                    </button>
                    <span x-show="createTab === 'info' && tempEventId" class="text-success">
                        <i class="bi bi-check-circle"></i> Événement créé ! Configurez les lecteurs ou fermez le modal.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Reader Create/Edit Modal (for new event) -->
    <div class="modal" :class="{'show d-block': showCreateReaderModal}" tabindex="-1" style="background: rgba(0,0,0,0.6); z-index: 1060;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi" :class="createReaderEditMode ? 'bi-pencil' : 'bi-plus-circle'"></i>
                        <span x-text="createReaderEditMode ? 'Modifier le lecteur' : 'Ajouter un lecteur'"></span>
                    </h5>
                    <button type="button" class="btn-close" @click="closeCreateReaderModal()"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveCreateReader">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Numéro de série *</label>
                                <input type="text" class="form-control" x-model="currentCreateReader.serial"
                                       required placeholder="Ex: 107, 112, 120">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Localisation *</label>
                                <select class="form-select" x-model="currentCreateReader.location" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="DEPART">DEPART</option>
                                    <option value="Inter1">Inter1</option>
                                    <option value="Inter2">Inter2</option>
                                    <option value="Inter3">Inter3</option>
                                    <option value="Inter4">Inter4</option>
                                    <option value="Inter5">Inter5</option>
                                    <option value="Inter6">Inter6</option>
                                    <option value="Inter7">Inter7</option>
                                    <option value="Inter8">Inter8</option>
                                    <option value="Inter9">Inter9</option>
                                    <option value="Inter10">Inter10</option>
                                    <option value="ARRIVEE">ARRIVEE</option>
                                </select>
                                <small class="text-muted">Type de checkpoint pour le routage automatique des détections</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mode de chronométrage *</label>
                                <select class="form-select" x-model="currentCreateReader.mode" required>
                                    <option value="single_reader_simple">Lecteur unique - Plages horaires</option>
                                    <option value="single_reader_waves">Lecteur unique - Vagues + TOP départ</option>
                                    <option value="multi_reader">Multi lecteurs - Checkpoints fixes</option>
                                    <option value="multi_reader_waves">Multi lecteurs - Vagues + Départ groupé</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Distance depuis départ (km) *</label>
                                <input type="number" step="0.01" class="form-control"
                                       x-model="currentCreateReader.distance_from_start"
                                       required placeholder="Ex: 0, 5, 10, 21">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Anti-rebond (secondes)</label>
                                <input type="number" class="form-control" x-model="currentCreateReader.anti_rebounce_seconds"
                                       placeholder="3">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parcours associé</label>
                                <select class="form-select" x-model="currentCreateReader.race_id">
                                    <option value="">Aucun (tous les parcours)</option>
                                    <template x-for="race in createRaces" :key="race.id">
                                        <option :value="race.id" x-text="race.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Plages horaires (uniquement pour mode single_reader_simple) -->
                        <div x-show="currentCreateReader.mode === 'single_reader_simple'">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-flag"></i> Mode DEPART - Début
                                    </label>
                                    <input type="time" class="form-control" x-model="currentCreateReader.depart_time_start"
                                           placeholder="15:00">
                                    <small class="text-muted">Heure de début du mode DEPART (ex: 15:00)</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-flag"></i> Mode DEPART - Fin
                                    </label>
                                    <input type="time" class="form-control" x-model="currentCreateReader.depart_time_end"
                                           placeholder="15:30">
                                    <small class="text-muted">Heure de fin du mode DEPART (ex: 15:30)</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-flag-fill"></i> Mode ARRIVEE - Début
                                    </label>
                                    <input type="time" class="form-control" x-model="currentCreateReader.arrival_time_start"
                                           placeholder="15:30">
                                    <small class="text-muted">Heure de début du mode ARRIVEE (ex: 15:30)</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-flag-fill"></i> Mode ARRIVEE - Fin
                                    </label>
                                    <input type="time" class="form-control" x-model="currentCreateReader.arrival_time_end"
                                           placeholder="19:00">
                                    <small class="text-muted">Heure de fin (optionnel, vide = jusqu'à la fin)</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="createReaderActive" x-model="currentCreateReader.is_active">
                            <label class="form-check-label" for="createReaderActive">Lecteur actif</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeCreateReaderModal()">Annuler</button>
                    <button type="button" class="btn btn-primary" @click="saveCreateReader()" :disabled="savingCreateReader">
                        <i class="bi" :class="savingCreateReader ? 'bi-hourglass-split' : 'bi-save'"></i>
                        <span x-text="savingCreateReader ? 'Enregistrement...' : 'Enregistrer'"></span>
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
                                        <th>Localisation</th>
                                        <th>Mode</th>
                                        <th>Distance (km)</th>
                                        <th>Plages horaires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="reader in sortedReaders" :key="reader.id">
                                        <tr>
                                            <td><strong x-text="reader.serial"></strong></td>
                                            <td><span class="badge bg-secondary" x-text="reader.location || 'Non défini'"></span></td>
                                            <td>
                                                <span class="badge badge-sm" :class="{
                                                    'bg-info': reader.mode === 'single_reader_simple',
                                                    'bg-primary': reader.mode === 'single_reader_waves',
                                                    'bg-success': reader.mode === 'multi_reader',
                                                    'bg-warning': reader.mode === 'multi_reader_waves'
                                                }" x-text="getModeLabel(reader.mode || 'multi_reader')" style="font-size: 0.7rem;"></span>
                                            </td>
                                            <td x-text="reader.distance_from_start + ' km'"></td>
                                            <td>
                                                <template x-if="reader.depart_time_start || reader.arrival_time_start">
                                                    <div style="font-size: 0.85rem;">
                                                        <div x-show="reader.depart_time_start" class="text-success">
                                                            <i class="bi bi-flag"></i> DEPART:
                                                            <strong x-text="reader.depart_time_start?.substring(0,5)"></strong> -
                                                            <strong x-text="reader.depart_time_end?.substring(0,5)"></strong>
                                                        </div>
                                                        <div x-show="reader.arrival_time_start" class="text-primary">
                                                            <i class="bi bi-flag-fill"></i> ARRIVEE:
                                                            <strong x-text="reader.arrival_time_start?.substring(0,5)"></strong>
                                                            <span x-show="reader.arrival_time_end">
                                                                - <strong x-text="reader.arrival_time_end?.substring(0,5)"></strong>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="!reader.depart_time_start && !reader.arrival_time_start">
                                                    <span class="text-muted small">-</span>
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
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Numéro de série *</label>
                                <input type="text" class="form-control" x-model="currentReader.serial"
                                       required placeholder="Ex: 107, 112, 120">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Localisation *</label>
                                <select class="form-select" x-model="currentReader.location" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="DEPART">DEPART</option>
                                    <option value="Inter1">Inter1</option>
                                    <option value="Inter2">Inter2</option>
                                    <option value="Inter3">Inter3</option>
                                    <option value="Inter4">Inter4</option>
                                    <option value="Inter5">Inter5</option>
                                    <option value="Inter6">Inter6</option>
                                    <option value="Inter7">Inter7</option>
                                    <option value="Inter8">Inter8</option>
                                    <option value="Inter9">Inter9</option>
                                    <option value="Inter10">Inter10</option>
                                    <option value="ARRIVEE">ARRIVEE</option>
                                </select>
                                <small class="text-muted">Type de checkpoint pour le routage automatique des détections</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mode de chronométrage *</label>
                                <select class="form-select" x-model="currentReader.mode" required>
                                    <option value="single_reader_simple">Lecteur unique - Plages horaires</option>
                                    <option value="single_reader_waves">Lecteur unique - Vagues + TOP départ</option>
                                    <option value="multi_reader">Multi lecteurs - Checkpoints fixes</option>
                                    <option value="multi_reader_waves">Multi lecteurs - Vagues + Départ groupé</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Distance depuis départ (km) *</label>
                                <input type="number" step="0.01" class="form-control"
                                       x-model="currentReader.distance_from_start"
                                       required placeholder="Ex: 0, 5, 10, 21">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Anti-rebond (secondes)</label>
                                <input type="number" class="form-control" x-model="currentReader.anti_rebounce_seconds"
                                       placeholder="3">
                            </div>
                        </div>

                        <div class="row">
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

                        <!-- Plages horaires (uniquement pour mode single_reader_simple) -->
                        <div x-show="currentReader.mode === 'single_reader_simple'">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-flag"></i> Mode DEPART - Début
                                    </label>
                                    <input type="time" class="form-control" x-model="currentReader.depart_time_start"
                                           placeholder="15:00">
                                    <small class="text-muted">Heure de début du mode DEPART (ex: 15:00)</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-flag"></i> Mode DEPART - Fin
                                    </label>
                                    <input type="time" class="form-control" x-model="currentReader.depart_time_end"
                                           placeholder="15:30">
                                    <small class="text-muted">Heure de fin du mode DEPART (ex: 15:30)</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-flag-fill"></i> Mode ARRIVEE - Début
                                    </label>
                                    <input type="time" class="form-control" x-model="currentReader.arrival_time_start"
                                           placeholder="15:30">
                                    <small class="text-muted">Heure de début du mode ARRIVEE (ex: 15:30)</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-flag-fill"></i> Mode ARRIVEE - Fin
                                    </label>
                                    <input type="time" class="form-control" x-model="currentReader.arrival_time_end"
                                           placeholder="19:00">
                                    <small class="text-muted">Heure de fin (optionnel, vide = jusqu'à la fin)</small>
                                </div>
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

        // Reader management (for edit modal)
        readers: [],
        races: [],
        loadingReaders: false,
        readerEditMode: false,
        savingReader: false,
        currentReader: {},

        // Reader management (for create modal)
        createTab: 'info',
        createReaders: [],
        createRaces: [],
        loadingCreateReaders: false,
        showCreateReaderModal: false,
        createReaderEditMode: false,
        savingCreateReader: false,
        currentCreateReader: {},
        tempEventId: null,

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
                console.log('Creating event with data:', this.newEvent);
                const response = await axios.post('/events', this.newEvent);
                this.tempEventId = response.data.id;
                await this.loadEvents();

                // Passer à l'onglet lecteurs après création
                this.createTab = 'readers';
                await this.loadCreateReaders();

                alert('Événement créé avec succès ! Vous pouvez maintenant configurer les lecteurs RFID.');
            } catch (error) {
                console.error('Error creating event:', error);
                console.error('Error response:', error.response?.data);
                console.error('Error status:', error.response?.status);

                let errorMessage = 'Erreur lors de la création de l\'événement';
                if (error.response?.data?.errors) {
                    const errors = Object.values(error.response.data.errors).flat();
                    errorMessage += ':\n' + errors.join('\n');
                } else if (error.response?.data?.message) {
                    errorMessage += ': ' + error.response.data.message;
                }

                alert(errorMessage);
            }
        },

        closeCreateModal() {
            this.showCreateModal = false;
            this.createTab = 'info';
            this.createReaders = [];
            this.createRaces = [];
            this.tempEventId = null;
            this.resetForm();
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

        getModeLabel(mode) {
            const labels = {
                'single_reader_simple': 'Simple',
                'single_reader_waves': 'Vagues',
                'multi_reader': 'Multi',
                'multi_reader_waves': 'Multi+Vagues'
            };
            return labels[mode] || 'Multi';
        },

        openReaderModal() {
            this.readerEditMode = false;
            this.currentReader = {
                serial: '',
                location: '',
                mode: 'multi_reader',
                distance_from_start: 0,
                anti_rebounce_seconds: 3,
                event_id: this.editingEvent.id,
                race_id: '',
                is_active: true,
                depart_time_start: '',
                depart_time_end: '',
                arrival_time_start: '',
                arrival_time_end: ''
            };
            this.showReaderModal = true;
        },

        editReader(reader) {
            this.readerEditMode = true;
            this.currentReader = { ...reader };
            this.showReaderModal = true;
        },

        closeReaderModal() {
            this.showReaderModal = false;
            this.currentReader = {};
        },

        async saveReader() {
            this.savingReader = true;
            try {
                if (this.readerEditMode) {
                    await axios.put(`/readers/${this.currentReader.id}`, this.currentReader);
                } else {
                    await axios.post('/readers', this.currentReader);
                }
                // Recharger la page pour mettre à jour la navbar (onglet Vagues)
                window.location.reload();
            } catch (error) {
                console.error('Error saving reader:', error);
                alert('Erreur lors de l\'enregistrement du lecteur');
                this.savingReader = false;
            }
        },

        async deleteReader(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce lecteur ?')) return;

            try {
                await axios.delete(`/readers/${id}`);
                // Recharger la page pour mettre à jour la navbar (onglet Vagues)
                window.location.reload();
            } catch (error) {
                console.error('Error deleting reader:', error);
                alert('Erreur lors de la suppression du lecteur');
            }
        },

        // ============ READER MANAGEMENT FOR CREATE MODAL ============

        async loadCreateReaders() {
            if (!this.tempEventId) {
                this.createReaders = [];
                this.loadingCreateReaders = false;
                return;
            }

            this.loadingCreateReaders = true;
            try {
                const response = await axios.get(`/readers/event/${this.tempEventId}`);
                this.createReaders = response.data;
                await this.loadCreateRaces();
            } catch (error) {
                console.error('Error loading readers:', error);
                alert('Erreur lors du chargement des lecteurs');
            } finally {
                this.loadingCreateReaders = false;
            }
        },

        async loadCreateRaces() {
            if (!this.tempEventId) return;

            try {
                const response = await axios.get(`/races/event/${this.tempEventId}`);
                this.createRaces = response.data;
            } catch (error) {
                console.error('Error loading races:', error);
            }
        },

        get sortedCreateReaders() {
            return [...this.createReaders].sort((a, b) => {
                return parseFloat(a.distance_from_start || 0) - parseFloat(b.distance_from_start || 0);
            });
        },

        openCreateReaderModal() {
            this.createReaderEditMode = false;
            this.currentCreateReader = {
                serial: '',
                location: '',
                mode: 'multi_reader',
                distance_from_start: 0,
                anti_rebounce_seconds: 3,
                event_id: this.tempEventId,
                race_id: '',
                is_active: true,
                depart_time_start: '',
                depart_time_end: '',
                arrival_time_start: '',
                arrival_time_end: ''
            };
            this.showCreateReaderModal = true;
        },

        editCreateReader(reader) {
            this.createReaderEditMode = true;
            this.currentCreateReader = { ...reader };
            this.showCreateReaderModal = true;
        },

        closeCreateReaderModal() {
            this.showCreateReaderModal = false;
            this.currentCreateReader = {};
        },

        async saveCreateReader() {
            this.savingCreateReader = true;
            try {
                if (this.createReaderEditMode) {
                    await axios.put(`/readers/${this.currentCreateReader.id}`, this.currentCreateReader);
                } else {
                    await axios.post('/readers', this.currentCreateReader);
                }
                this.closeCreateReaderModal();
                await this.loadCreateReaders();
                this.savingCreateReader = false;
            } catch (error) {
                console.error('Error saving reader:', error);
                alert('Erreur lors de l\'enregistrement du lecteur');
                this.savingCreateReader = false;
            }
        },

        async deleteCreateReader(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce lecteur ?')) return;

            try {
                await axios.delete(`/readers/${id}`);
                await this.loadCreateReaders();
            } catch (error) {
                console.error('Error deleting reader:', error);
                alert('Erreur lors de la suppression du lecteur');
            }
        }
    }
}
</script>
@endsection
