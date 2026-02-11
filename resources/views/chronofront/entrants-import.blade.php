@extends('chronofront.layout')

@section('title', 'Import CSV Participants')

@section('content')
<div class="container-fluid" x-data="importCSV()">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2"><i class="bi bi-upload text-success"></i> Import CSV Participants</h1>
            <p class="text-muted">Importez vos participants depuis un fichier CSV. Les parcours et vagues seront créés automatiquement.</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <div x-show="successMessage" x-transition class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> <span x-text="successMessage"></span>
        <button type="button" class="btn-close" @click="successMessage = null"></button>
    </div>

    <div x-show="errorMessage" x-transition class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <span x-text="errorMessage"></span>
        <button type="button" class="btn-close" @click="errorMessage = null"></button>
    </div>

    <div class="row">
        <!-- Upload Form -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-arrow-up"></i> Importer un fichier CSV</h5>
                </div>
                <div class="card-body">
                    <form @submit.prevent="uploadCSV">
                        <!-- Event Selection -->
                        <div class="mb-3">
                            <label for="eventSelect" class="form-label">Événement <span class="text-danger">*</span></label>
                            <select
                                id="eventSelect"
                                class="form-select"
                                x-model="selectedEventId"
                                required
                                :disabled="uploading"
                            >
                                <option value="">-- Sélectionnez un événement --</option>
                                <template x-for="event in events" :key="event.id">
                                    <option :value="event.id" x-text="event.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- File Input -->
                        <div class="mb-3">
                            <label for="csvFile" class="form-label">Fichier CSV <span class="text-danger">*</span></label>
                            <input
                                type="file"
                                class="form-control"
                                id="csvFile"
                                accept=".csv,.txt"
                                @change="handleFileSelect"
                                required
                                :disabled="uploading"
                            >
                            <div class="form-text">Format accepté : .csv (max 10MB)</div>
                        </div>

                        <!-- File Info -->
                        <div x-show="selectedFile" class="alert alert-info">
                            <i class="bi bi-file-earmark-text"></i>
                            <strong>Fichier sélectionné :</strong> <span x-text="selectedFile?.name"></span>
                            (<span x-text="formatFileSize(selectedFile?.size)"></span>)
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="btn btn-success w-100"
                            :disabled="!selectedFile || !selectedEventId || uploading"
                        >
                            <span x-show="!uploading">
                                <i class="bi bi-upload"></i> Importer les participants
                            </span>
                            <span x-show="uploading">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Importation en cours...
                            </span>
                        </button>
                    </form>

                    <!-- Progress -->
                    <div x-show="uploading" class="mt-3">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>

                    <!-- Import Results -->
                    <div x-show="importResults" x-transition class="mt-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-clipboard-check"></i> Résultats de l'import</h6>
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Participants importés :</strong> <span class="text-success" x-text="importResults?.imported"></span></li>
                                    <li><strong>Lignes traitées :</strong> <span x-text="importResults?.total_rows"></span></li>
                                    <li><strong>Épreuves créées :</strong> <span class="text-info" x-text="importResults?.races_created"></span></li>
                                    <li><strong>Vagues créées :</strong> <span class="text-info" x-text="importResults?.waves_created"></span></li>
                                    <li x-show="importResults?.errors?.length > 0">
                                        <strong class="text-warning">Erreurs :</strong> <span x-text="importResults?.errors?.length"></span>
                                    </li>
                                </ul>

                                <!-- Errors Details -->
                                <div x-show="importResults?.errors?.length > 0" class="mt-2">
                                    <details>
                                        <summary class="text-warning" style="cursor: pointer;">Voir les erreurs</summary>
                                        <ul class="mt-2 mb-0 small">
                                            <template x-for="(error, index) in importResults?.errors" :key="index">
                                                <li x-text="error"></li>
                                            </template>
                                        </ul>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Format Info -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Format fichier d'inscription</h5>
                </div>
                <div class="card-body">
                    <p><strong>En-tête supporté :</strong> dossard / nom / prenom / sexe / naissance / parcours / cat / vague / club</p>

                    <h6 class="mt-3">Exemple de fichier CSV :</h6>
                    <pre class="bg-light p-3 rounded"><code>dossard,nom,prenom,sexe,naissance,parcours,cat,vague,club
3,DUPONT,Jean,M,15/05/1985,10km,M0,Vague 1,AS SETE
125,MARTIN,Sophie,F,22/03/1990,5km,F0,Vague 2,RC MONTPELLIER</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function importCSV() {
    return {
        events: [],
        selectedEventId: '',
        selectedFile: null,
        uploading: false,
        successMessage: null,
        errorMessage: null,
        importResults: null,

        init() {
            this.loadEvents();
        },

        async loadEvents() {
            try {
                const response = await axios.get('/events');
                this.events = response.data;
            } catch (error) {
                this.errorMessage = 'Erreur lors du chargement des événements';
                console.error(error);
            }
        },

        handleFileSelect(event) {
            this.selectedFile = event.target.files[0];
            this.importResults = null;
        },

        formatFileSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        async uploadCSV() {
            if (!this.selectedFile || !this.selectedEventId) return;

            this.uploading = true;
            this.successMessage = null;
            this.errorMessage = null;
            this.importResults = null;

            try {
                const formData = new FormData();
                formData.append('file', this.selectedFile);
                formData.append('event_id', this.selectedEventId);

                const response = await axios.post('/entrants/import', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                this.importResults = response.data;
                this.successMessage = `Import réussi ! ${response.data.imported} participants importés.`;

                // Reset form
                document.getElementById('csvFile').value = '';
                this.selectedFile = null;

            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Erreur lors de l\'import du fichier CSV';
                console.error(error);
            } finally {
                this.uploading = false;
            }
        }
    }
}
</script>
@endsection
