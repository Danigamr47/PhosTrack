@extends('template')
@section('title', 'Levés')

@section('content')
@php($estTechnicien = auth()->user()->aLeRole(\App\Enums\Role::Technicien))

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Levés</h1>
    @if ($estTechnicien)
        <a href="{{ route('leves.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nouveau levé
        </a>
    @endif
</div>
@if ($stockFiltre)
    <div class="alert alert-info d-flex justify-content-between align-items-center py-2">
        <span>Historique du stock : <strong>{{ $stockFiltre->nom }}</strong></span>
        <a href="{{ route('leves.index') }}" class="btn btn-sm btn-outline-primary">Tout afficher</a>
    </div>
@endif
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Date</th><th>Stock</th><th>Campagne</th>
                <th class="text-end">Volume (m³)</th><th>Levé par</th><th>Statut</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($leves as $l)
            <tr>
                <td>{{ $l->date_leve->format('d/m/Y') }}</td>
                <td>{{ $l->stock->nom }}
                    <span class="badge text-bg-secondary">{{ $l->stock->typeMateriau->libelle }}</span></td>
                <td>{{ $l->campagne->libelle }}</td>
                <td class="text-end">{{ $l->volume !== null ? number_format($l->volume, 0, ',', ' ') : '—' }}</td>
                <td>{{ $l->technicien->matricule }}</td>
                <td><span class="badge text-bg-{{ $l->statut->couleur() }}">{{ $l->statut->libelle() }}</span></td>
                <td class="text-end">
                    <a href="{{ route('leves.show', $l) }}" class="btn btn-sm btn-outline-secondary">Voir</a>
                    @if ($estTechnicien && ! $l->estValide())
                        <a href="{{ route('leves.edit', $l) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                        <form method="POST" action="{{ route('leves.destroy', $l) }}" class="d-inline"
                              onsubmit="return confirm('Supprimer ce levé ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted">Aucun levé à afficher.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>

<div class="mt-3">{{ $leves->links('pagination::bootstrap-5') }}</div>
@endsection