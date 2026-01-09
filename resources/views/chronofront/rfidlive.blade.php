<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Live - Détections en temps réel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body {
            background: #0a0a0a;
            color: #f0f0f0;
            font-family: 'Courier New', monospace;
        }
        .header-bar {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            border-bottom: 2px solid #FFD700;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .detection-table {
            font-size: 0.9rem;
        }
        .detection-table thead th {
            background: #1a1a1a;
            color: #FFD700;
            position: sticky;
            top: 60px;
            z-index: 10;
            border-bottom: 2px solid #FFD700;
        }
        .detection-table tbody tr {
            background: #0f0f0f;
            transition: background 0.2s;
        }
        .detection-table tbody tr:hover {
            background: #1a1a1a;
        }
        .detection-table tbody tr.new-detection {
            animation: highlight 1s ease-in-out;
        }
        @keyframes highlight {
            0%, 100% { background: #0f0f0f; }
            50% { background: #2a4a2a; }
        }
        .badge-valid {
            background: #28a745;
        }
        .badge-invalid {
            background: #dc3545;
        }
        .badge-pending {
            background: #ffc107;
            color: #000;
        }
        .rfid-tag {
            font-family: 'Courier New', monospace;
            color: #00D9FF;
            font-weight: bold;
        }
        .detection-time {
            color: #FFD700;
            font-variant-numeric: tabular-nums;
        }
        .counter {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        .counter-value {
            font-size: 2rem;
            color: #FFD700;
            font-weight: bold;
        }
        .counter-label {
            font-size: 0.9rem;
            color: #999;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div x-data="rfidLive()" x-init="init()">
        <!-- Header -->
        <div class="header-bar">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-broadcast text-warning"></i>
                        RFID Live - Détections en temps réel
                    </h1>
                    <p class="text-muted mb-0 small">Interface de debug pour tester les communications RFID</p>
                </div>
                <div class="d-flex gap-3">
                    <div class="counter">
                        <div class="counter-value" x-text="detections.length"></div>
                        <div class="counter-label">Détections</div>
                    </div>
                    <div class="counter">
                        <div class="counter-value" x-text="validCount"></div>
                        <div class="counter-label">Valides</div>
                    </div>
                    <div class="counter">
                        <div class="counter-value" x-text="invalidCount"></div>
                        <div class="counter-label">Invalides</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Controls -->
        <div class="container-fluid mt-3">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label text-muted">Rechercher</label>
                    <input type="text" class="form-control" x-model="searchQuery" placeholder="RFID, Dossard, Nom...">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Statut</label>
                    <select class="form-select" x-model="statusFilter">
                        <option value="">Tous</option>
                        <option value="valid">Valides uniquement</option>
                        <option value="invalid">Invalides uniquement</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Limite</label>
                    <select class="form-select" x-model="limit" @change="loadDetections">
                        <option value="100">100 dernières</option>
                        <option value="500">500 dernières</option>
                        <option value="1000">1000 dernières</option>
                        <option value="5000">5000 dernières</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button class="btn btn-warning" @click="loadDetections">
                        <i class="bi bi-arrow-clockwise"></i> Rafraîchir
                    </button>
                    <button class="btn btn-danger" @click="clearDetections">
                        <i class="bi bi-trash"></i> Vider
                    </button>
                    <div class="form-check form-switch d-flex align-items-center">
                        <input class="form-check-input" type="checkbox" x-model="autoRefresh" id="autoRefresh">
                        <label class="form-check-label ms-2 text-muted" for="autoRefresh">
                            Auto-refresh (2s)
                        </label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <span class="badge bg-secondary" x-show="loading">
                        <i class="bi bi-arrow-repeat"></i> Chargement...
                    </span>
                    <span class="badge bg-success" x-show="!loading && lastUpdate">
                        <i class="bi bi-check-circle"></i> Mis à jour <span x-text="lastUpdate"></span>
                    </span>
                </div>
            </div>

            <!-- Detections Table -->
            <div class="table-responsive">
                <table class="table table-dark table-striped detection-table">
                    <thead>
                        <tr>
                            <th style="width: 3%">#</th>
                            <th style="width: 10%">Timestamp</th>
                            <th style="width: 10%">RFID Tag</th>
                            <th style="width: 6%">Dossard</th>
                            <th style="width: 15%">Nom</th>
                            <th style="width: 6%">Cat.</th>
                            <th style="width: 12%">Parcours</th>
                            <th style="width: 8%">Lecteur</th>
                            <th style="width: 6%">Tour</th>
                            <th style="width: 8%">Temps</th>
                            <th style="width: 8%">Vitesse</th>
                            <th style="width: 8%">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="filteredDetections.length === 0">
                            <tr>
                                <td colspan="12" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-3">Aucune détection</p>
                                </td>
                            </tr>
                        </template>
                        <template x-for="(detection, index) in filteredDetections" :key="detection.id">
                            <tr :class="{ 'new-detection': detection.is_new }">
                                <td class="text-muted" x-text="index + 1"></td>
                                <td class="detection-time" x-text="formatTimestamp(detection.created_at)"></td>
                                <td class="rfid-tag" x-text="detection.rfid_tag || '-'"></td>
                                <td x-text="detection.entrant?.bib_number || '-'"></td>
                                <td x-text="detection.entrant ? (detection.entrant.firstname + ' ' + detection.entrant.lastname) : '-'"></td>
                                <td x-text="detection.entrant?.category?.name || '-'"></td>
                                <td x-text="detection.race?.name || '-'"></td>
                                <td x-text="detection.reader_location || detection.reader?.name || '-'"></td>
                                <td x-text="detection.lap_number || '-'"></td>
                                <td x-text="detection.formatted_time || '-'"></td>
                                <td x-text="detection.speed ? detection.speed + ' km/h' : '-'"></td>
                                <td>
                                    <span class="badge"
                                          :class="{
                                              'badge-valid': detection.status === 'V',
                                              'badge-invalid': detection.status === 'I',
                                              'badge-pending': !detection.status || detection.status === 'P'
                                          }"
                                          x-text="getStatusLabel(detection.status)">
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function rfidLive() {
            return {
                detections: [],
                searchQuery: '',
                statusFilter: '',
                limit: 500,
                loading: false,
                autoRefresh: true,
                lastUpdate: null,
                lastDetectionId: 0,

                get filteredDetections() {
                    let filtered = this.detections;

                    // Search filter
                    if (this.searchQuery) {
                        const query = this.searchQuery.toLowerCase();
                        filtered = filtered.filter(d =>
                            d.rfid_tag?.toLowerCase().includes(query) ||
                            d.entrant?.bib_number?.toString().includes(query) ||
                            d.entrant?.firstname?.toLowerCase().includes(query) ||
                            d.entrant?.lastname?.toLowerCase().includes(query)
                        );
                    }

                    // Status filter
                    if (this.statusFilter === 'valid') {
                        filtered = filtered.filter(d => d.status === 'V');
                    } else if (this.statusFilter === 'invalid') {
                        filtered = filtered.filter(d => d.status === 'I');
                    }

                    return filtered;
                },

                get validCount() {
                    return this.detections.filter(d => d.status === 'V').length;
                },

                get invalidCount() {
                    return this.detections.filter(d => d.status === 'I').length;
                },

                init() {
                    this.loadDetections();
                    this.startAutoRefresh();
                },

                async loadDetections() {
                    this.loading = true;
                    try {
                        const response = await axios.get(`/api/results/all-detections?limit=${this.limit}`);

                        // Mark new detections
                        const newDetections = response.data.map(d => ({
                            ...d,
                            is_new: d.id > this.lastDetectionId
                        }));

                        if (newDetections.length > 0) {
                            this.lastDetectionId = Math.max(...newDetections.map(d => d.id));
                        }

                        this.detections = newDetections;
                        this.lastUpdate = new Date().toLocaleTimeString('fr-FR');

                        // Remove 'new' class after animation
                        setTimeout(() => {
                            this.detections = this.detections.map(d => ({ ...d, is_new: false }));
                        }, 1000);

                    } catch (error) {
                        console.error('Erreur lors du chargement des détections', error);
                    } finally {
                        this.loading = false;
                    }
                },

                startAutoRefresh() {
                    setInterval(() => {
                        if (this.autoRefresh) {
                            this.loadDetections();
                        }
                    }, 2000);
                },

                clearDetections() {
                    if (confirm('Êtes-vous sûr de vouloir vider la liste des détections affichées ?')) {
                        this.detections = [];
                        this.lastDetectionId = 0;
                    }
                },

                formatTimestamp(timestamp) {
                    if (!timestamp) return '-';
                    const date = new Date(timestamp);
                    return date.toLocaleString('fr-FR', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                },

                getStatusLabel(status) {
                    switch(status) {
                        case 'V': return 'Valide';
                        case 'I': return 'Invalide';
                        case 'P': return 'En attente';
                        default: return 'Inconnu';
                    }
                }
            }
        }
    </script>
</body>
</html>
