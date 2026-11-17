@extends('template')

@section('title', 'Utilisateurs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted small mb-1">Administration › Sécurité</p>
        <h1 class="h4 mb-0 fw-bold">Gestion des comptes</h1>
    </div>
    <a href="{{ route('admin.utilisateurs.create') }}" class="btn btn-primary btn-sm shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Nouveau compte
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small">
                <tr>
                    <th class="ps-4">Matricule</th>
                    <th>Nom complet</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Dernière connexion</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($utilisateurs as $u)
                <tr>
                    <td class="ps-4">
                        @if ($enLigne->contains($u->id))
                            <i class="bi bi-circle-fill text-success small me-1" title="En ligne"></i>
                        @endif
                        <span class="font-monospace fw-semibold">{{ $u->matricule }}</span>
                    </td>
                    <td class="fw-semibold">{{ $u->nom_complet }}</td>
                    <td><span class="badge text-bg-secondary">{{ $u->role->libelle() }}</span></td>
                    <td>
                        @if ($u->actif)
                            <span class="badge text-bg-success">Actif</span>
                        @else
                            <span class="badge text-bg-danger">Désactivé</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ $u->connexions_max_date_connexion ? \Illuminate\Support\Carbon::parse($u->connexions_max_date_connexion)->format('d/m/Y H:i') : '—' }}</td>
                    <td class="text-end pe-4">
                        {{-- Bouton Modifier / Réinitialiser le mot de passe --}}
                        <a href="{{ route('admin.utilisateurs.edit', $u) }}" class="btn btn-sm btn-outline-primary border-0" title="Modifier le compte ou réinitialiser le mot de passe">
                            <i class="bi bi-key me-1"></i> Gérer
                        </a>
                        
                        {{-- Activer / Désactiver le compte --}}
                        <form method="POST" action="{{ route('admin.utilisateurs.statut', $u) }}" class="d-inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-{{ $u->actif ? 'warning' : 'success' }} border-0" title="{{ $u->actif ? 'Désactiver' : 'Activer' }}">
                                <i class="bi bi-{{ $u->actif ? 'lock' : 'unlock' }}"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $utilisateurs->links('pagination::bootstrap-5') }}</div>
<p class="text-muted small mt-2"><i class="bi bi-circle-fill text-success small"></i> = actif au cours des 5 dernières minutes</p>
@endsection