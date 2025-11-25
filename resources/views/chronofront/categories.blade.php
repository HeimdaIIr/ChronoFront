@extends('chronofront.layout')

@section('title', 'Catégories FFA')

@section('content')
<div class="container-fluid" x-data="categoriesManager()">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2"><i class="bi bi-grid text-info"></i> Catégories FFA</h1>
            <p class="text-muted">Gérez les catégories d'âge FFA pour vos courses</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-warning me-2" @click="initFFA" :disabled="loading || initializing">
                <i class="bi bi-magic"></i>
                <span x-show="!initializing">Initialiser FFA 2025 (36 catégories)</span>
                <span x-show="initializing">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Initialisation...
                </span>
            </button>
            <button class="btn btn-primary" @click="openCreateModal">
                <i class="bi bi-plus-circle"></i> Nouvelle Catégorie
            </button>
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

    <!-- Info Card -->
    <div class="card shadow-sm mb-4 border-info">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <i class="bi bi-info-circle-fill text-info" style="font-size: 2rem;"></i>
                </div>
                <div class="col">
                    <h6 class="mb-1">Catégories FFA (Fédération Française d'Athlétisme) 2025</h6>
                    <p class="mb-0 text-muted small">
                        Les catégories sont automatiquement attribuées aux participants en fonction de leur âge et sexe lors de l'import CSV.
                        Utilisez "Initialiser FFA 2025" pour créer les 36 catégories officielles :
                        <strong>Jeunes</strong> (BB, EA, PO, BE, MI, CA, JU),
                        <strong>Adultes</strong> (ES, SE),
                        <strong>Masters</strong> (M0 à M10) - pour hommes et femmes.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div x-show="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>

            <div x-show="!loading && categories.length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3 mb-3">Aucune catégorie définie</p>
                <button class="btn btn-warning" @click="initFFA">
                    <i class="bi bi-magic"></i> Initialiser les catégories FFA
                </button>
            </div>

            <div x-show="!loading && categories.length > 0">
                <!-- Male Categories -->
                <div class="mb-5">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="bi bi-person-fill text-primary"></i> Hommes (M)
                        <span class="badge bg-primary ms-2" x-text="maleCategories.length + ' catégories'"></span>
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Âge Minimum</th>
                                    <th>Âge Maximum</th>
                                    <th>Couleur</th>
                                    <th>Participants</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="category in maleCategories" :key="category.id">
                                    <tr>
                                        <td>
                                            <strong x-text="category.name"></strong>
                                        </td>
                                        <td x-text="category.age_min + ' ans'"></td>
                                        <td x-text="category.age_max + ' ans'"></td>
                                        <td>
                                            <span
                                                x-show="category.color"
                                                class="badge"
                                                :style="`background-color: ${category.color}`"
                                                x-text="category.color"
                                            ></span>
                                            <span x-show="!category.color" class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info" x-text="(category.entrants?.length || 0)"></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" @click="openEditModal(category)" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" @click="deleteCategory(category)" title="Supprimer">
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

                <!-- Female Categories -->
                <div>
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="bi bi-person-fill text-danger"></i> Femmes (F)
                        <span class="badge bg-danger ms-2" x-text="femaleCategories.length + ' catégories'"></span>
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Âge Minimum</th>
                                    <th>Âge Maximum</th>
                                    <th>Couleur</th>
                                    <th>Participants</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="category in femaleCategories" :key="category.id">
                                    <tr>
                                        <td>
                                            <strong x-text="category.name"></strong>
                                        </td>
                                        <td x-text="category.age_min + ' ans'"></td>
                                        <td x-text="category.age_max + ' ans'"></td>
                                        <td>
                                            <span
                                                x-show="category.color"
                                                class="badge"
                                                :style="`background-color: ${category.color}`"
                                                x-text="category.color"
                                            ></span>
                                            <span x-show="!category.color" class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info" x-text="(category.entrants?.length || 0)"></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" @click="openEditModal(category)" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" @click="deleteCategory(category)" title="Supprimer">
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
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal fade" :class="{'show d-block': showModal}" tabindex="-1" style="background: rgba(0,0,0,0.5);" x-show="showModal" @click.self="closeModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="editingCategory ? 'Modifier la catégorie' : 'Nouvelle catégorie'"></h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <form @submit.prevent="saveCategory">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nom de la catégorie <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.name" required placeholder="Ex: SE-M, MA0-F...">
                            </div>

                            <div class="col-6">
                                <label class="form-label">Sexe <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="form.gender" required>
                                    <option value="">-- Sélectionnez --</option>
                                    <option value="M">Homme (M)</option>
                                    <option value="F">Femme (F)</option>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label">Couleur</label>
                                <input type="color" class="form-control form-control-color" x-model="form.color">
                            </div>

                            <div class="col-6">
                                <label class="form-label">Âge minimum <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" x-model="form.age_min" required min="0" max="150">
                            </div>

                            <div class="col-6">
                                <label class="form-label">Âge maximum <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" x-model="form.age_max" required min="0" max="150">
                            </div>

                            <div class="col-12">
                                <div class="alert alert-info small mb-0">
                                    <i class="bi bi-info-circle"></i> La catégorie sera automatiquement attribuée aux participants dont l'âge correspond.
                                </div>
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
function categoriesManager() {
    return {
        categories: [],
        maleCategories: [],
        femaleCategories: [],
        loading: false,
        initializing: false,
        showModal: false,
        editingCategory: null,
        saving: false,
        successMessage: null,
        errorMessage: null,
        form: {
            name: '',
            gender: '',
            age_min: '',
            age_max: '',
            color: '#3B82F6'
        },

        init() {
            this.loadCategories();
        },

        async loadCategories() {
            this.loading = true;
            try {
                const response = await axios.get('/categories');
                this.categories = response.data;
                this.separateByGender();
            } catch (error) {
                console.error('Erreur lors du chargement des catégories', error);
            } finally {
                this.loading = false;
            }
        },

        separateByGender() {
            this.maleCategories = this.categories
                .filter(c => c.gender === 'M')
                .sort((a, b) => a.age_min - b.age_min);

            this.femaleCategories = this.categories
                .filter(c => c.gender === 'F')
                .sort((a, b) => a.age_min - b.age_min);
        },

        async initFFA() {
            if (!confirm('Initialiser les 36 catégories FFA officielles 2025 ?\n\nATTENTION : Cela supprimera toutes les catégories existantes et les remplacera par les catégories officielles FFA (BB à M10 pour H/F).')) return;

            this.initializing = true;
            this.errorMessage = null;

            try {
                const response = await axios.post('/categories/init-ffa');
                this.successMessage = response.data.message;
                await this.loadCategories();
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Erreur lors de l\'initialisation';
            } finally {
                this.initializing = false;
            }
        },

        openCreateModal() {
            this.editingCategory = null;
            this.form = {
                name: '',
                gender: '',
                age_min: '',
                age_max: '',
                color: '#3B82F6'
            };
            this.showModal = true;
        },

        openEditModal(category) {
            this.editingCategory = category;
            this.form = {
                name: category.name,
                gender: category.gender,
                age_min: category.age_min,
                age_max: category.age_max,
                color: category.color || '#3B82F6'
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingCategory = null;
        },

        async saveCategory() {
            // Validation
            if (parseInt(this.form.age_max) < parseInt(this.form.age_min)) {
                this.errorMessage = 'L\'âge maximum doit être supérieur ou égal à l\'âge minimum';
                return;
            }

            this.saving = true;
            this.errorMessage = null;

            try {
                if (this.editingCategory) {
                    await axios.put(`/categories/${this.editingCategory.id}`, this.form);
                    this.successMessage = 'Catégorie modifiée avec succès';
                } else {
                    await axios.post('/categories', this.form);
                    this.successMessage = 'Catégorie créée avec succès';
                }
                this.closeModal();
                await this.loadCategories();
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Erreur lors de l\'enregistrement';
            } finally {
                this.saving = false;
            }
        },

        async deleteCategory(category) {
            const participantsCount = category.entrants?.length || 0;
            let confirmMessage = `Supprimer la catégorie "${category.name}" ?`;

            if (participantsCount > 0) {
                confirmMessage += `\n\nATTENTION : ${participantsCount} participant(s) sont associés à cette catégorie.`;
            }

            if (!confirm(confirmMessage)) return;

            try {
                await axios.delete(`/categories/${category.id}`);
                this.successMessage = `Catégorie "${category.name}" supprimée`;
                await this.loadCategories();
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Erreur lors de la suppression';
            }
        }
    }
}
</script>
@endsection
