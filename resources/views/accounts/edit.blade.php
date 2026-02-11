@extends('chronofront.layout')

@section('title', 'Modifier le compte')

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
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Modifier le compte : {{ $account->username }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('accounts.update', $account) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('username') is-invalid @enderror"
                                   id="username"
                                   name="username"
                                   value="{{ old('username', $account->username) }}"
                                   required
                                   autofocus>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email', $account->email) }}"
                                   placeholder="utilisateur@example.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel"
                                   class="form-control @error('telephone') is-invalid @enderror"
                                   id="telephone"
                                   name="telephone"
                                   value="{{ old('telephone', $account->telephone) }}"
                                   placeholder="+33 6 12 34 56 78">
                            @error('telephone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror"
                                    id="role"
                                    name="role"
                                    required>
                                <option value="admin" {{ old('role', $account->role) === 'admin' ? 'selected' : '' }}>
                                    Admin - Accès complet + gestion des comptes
                                </option>
                                <option value="orga" {{ old('role', $account->role) === 'orga' ? 'selected' : '' }}>
                                    Organisateur - Peut créer/modifier des données
                                </option>
                                <option value="viewer" {{ old('role', $account->role) === 'viewer' ? 'selected' : '' }}>
                                    Viewer - Lecture seule
                                </option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Base de données</label>
                        <input type="text"
                               class="form-control"
                               value="{{ $account->database_file }}"
                               disabled
                               readonly>
                        <small class="form-text text-muted">La base de données ne peut pas être modifiée</small>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-muted mb-3"><i class="bi bi-key"></i> Changer le mot de passe</h6>
                    <p class="small text-muted">Laissez vide si vous ne souhaitez pas changer le mot de passe</p>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   placeholder="••••••••">
                            <small class="form-text text-muted">Minimum 8 caractères</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password"
                                   class="form-control"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
