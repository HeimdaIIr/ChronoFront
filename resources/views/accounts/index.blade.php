@extends('chronofront.layout')

@section('title', 'Gestion des comptes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3"><i class="bi bi-people-fill"></i> Gestion des comptes</h1>
    <a href="{{ route('accounts.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nouveau compte
    </a>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul"></i> Liste des comptes
    </div>
    <div class="card-body">
        @if($accounts->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nom d'utilisateur</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Rôle</th>
                            <th>Base de données</th>
                            <th>Statut</th>
                            <th>Créé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($accounts as $account)
                            <tr>
                                <td>{{ $account->id }}</td>
                                <td><strong>{{ $account->username }}</strong></td>
                                <td>{{ $account->email ?? '-' }}</td>
                                <td>{{ $account->telephone ?? '-' }}</td>
                                <td>
                                    @if($account->role === 'admin')
                                        <span class="badge bg-danger"><i class="bi bi-shield-fill"></i> Admin</span>
                                    @elseif($account->role === 'orga')
                                        <span class="badge bg-primary"><i class="bi bi-gear-fill"></i> Organisateur</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="bi bi-eye-fill"></i> Viewer</span>
                                    @endif
                                </td>
                                <td><code class="small">{{ $account->database_file }}</code></td>
                                <td>
                                    @if($account->is_active)
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Actif</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Inactif</span>
                                    @endif
                                </td>
                                <td>{{ $account->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('accounts.edit', $account) }}"
                                           class="btn btn-outline-primary"
                                           title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST"
                                              action="{{ route('accounts.toggle', $account) }}"
                                              class="d-inline">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-outline-{{ $account->is_active ? 'warning' : 'success' }}"
                                                    title="{{ $account->is_active ? 'Désactiver' : 'Activer' }}">
                                                <i class="bi bi-{{ $account->is_active ? 'pause' : 'play' }}-circle"></i>
                                            </button>
                                        </form>
                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                title="Supprimer"
                                                onclick="confirmDelete({{ $account->id }}, '{{ $account->username }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <p class="text-muted mt-3">Aucun compte trouvé</p>
                <a href="{{ route('accounts.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Créer le premier compte
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le compte <strong id="deleteAccountName"></strong> ?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function confirmDelete(accountId, accountName) {
    document.getElementById('deleteAccountName').textContent = accountName;
    document.getElementById('deleteForm').action = `/accounts/${accountId}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endsection
