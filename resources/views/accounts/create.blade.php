@extends('chronofront.layout')

@section('title', 'Nouveau compte')

@section('content')
<div class="mb-4">
    <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Retour à la liste
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-plus"></i> Créer un nouveau compte</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('accounts.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="account_name" class="form-label">Nom du compte <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('account_name') is-invalid @enderror"
                                   id="account_name"
                                   name="account_name"
                                   value="{{ old('account_name') }}"
                                   placeholder="Ex: MHR_Montpellier"
                                   required
                                   autofocus>
                            <small class="form-text text-muted">Ce nom sera utilisé pour nommer la base de données</small>
                            @error('account_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('username') is-invalid @enderror"
                                   id="username"
                                   name="username"
                                   value="{{ old('username') }}"
                                   placeholder="nom.utilisateur"
                                   required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="utilisateur@example.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel"
                                   class="form-control @error('telephone') is-invalid @enderror"
                                   id="telephone"
                                   name="telephone"
                                   value="{{ old('telephone') }}"
                                   placeholder="+33 6 12 34 56 78">
                            @error('telephone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                        <select class="form-select @error('role') is-invalid @enderror"
                                id="role"
                                name="role"
                                required>
                            <option value="">Sélectionner un rôle...</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>
                                Admin - Accès complet + gestion des comptes
                            </option>
                            <option value="orga" {{ old('role') === 'orga' ? 'selected' : '' }}>
                                Organisateur - Peut créer/modifier des données
                            </option>
                            <option value="viewer" {{ old('role') === 'viewer' ? 'selected' : '' }}>
                                Viewer - Lecture seule
                            </option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   placeholder="••••••••"
                                   required>
                            <small class="form-text text-muted">Minimum 8 caractères</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                            <input type="password"
                                   class="form-control"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   placeholder="••••••••"
                                   required>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Note :</strong> Une base de données SQLite sera automatiquement créée pour ce compte avec un nom basé sur le "Nom du compte".
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Créer le compte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
