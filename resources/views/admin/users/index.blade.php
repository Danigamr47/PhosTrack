@extends('template')

@section('title', 'Utilisateurs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Gestion des comptes</h1>
    <a href="{{ route('admin.utilisateurs.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nouveau compte
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Matricule</th><th>Nom</th><th>Rôle</th><th>Statut</th>
                    <th>Dernière connexion</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($utilisateurs as $u)
                <tr>
                    <td>
                        @if ($enLigne->contains($u->id))
                            <i class="bi bi-circle-fill text-success small" title="En ligne"></i>
                        @endif
                        {{ $u->matricule }}
                    </td>
                    <td>{{ $u->nom_complet }}</td>
                    <td><span class="badge text-bg-secondary">{{ $u->role->libelle() }}</span></td>
                    <td>
                        @if ($u->actif)
                            <span class="badge text-bg-success">Actif</span>
                        @else
                            <span class="badge text-bg-danger">Désactivé</span>
                        @endif
                    </td>
                    <td>{{ $u->connexions_max_date_connexion ? \Illuminate\Support\Carbon::parse($u->connexions_max_date_connexion)->format('d/m/Y H:i') : '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.utilisateurs.edit', $u) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                        <form method="POST" action="{{ route('admin.utilisateurs.statut', $u) }}" class="d-inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-{{ $u->actif ? 'warning' : 'success' }}">
                                {{ $u->actif ? 'Désactiver' : 'Activer' }}
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