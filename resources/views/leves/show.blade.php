@extends('template')
@section('title', 'Levé')

@section('content')
@php($estTechnicien = auth()->user()->aLeRole(\App\Enums\Role::Technicien))

<p class="text-muted small mb-1">Levés › Détail</p>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">
        {{ $leve->stock->nom }} — {{ $leve->date_leve->format('d/m/Y') }}
        <span class="badge text-bg-{{ $leve->statut->couleur() }} ms-2">{{ $leve->statut->libelle() }}</span>
    </h1>
    <div>
        <a href="{{ route('leves.index') }}" class="btn btn-outline-secondary">Retour</a>
        @if ($estTechnicien && ! $leve->estValide())
            <a href="{{ route('leves.edit', $leve) }}" class="btn btn-outline-primary">Modifier</a>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4"><div class="card-body">
            <h2 class="h6 mb-3">Informations du levé</h2>
            <dl class="row mb-0">
                <dt class="col-sm-4">Stock</dt>
                <dd class="col-sm-8">{{ $leve->stock->nom }} ({{ $leve->stock->typeMateriau->libelle }})</dd>
                <dt class="col-sm-4">Campagne</dt>
                <dd class="col-sm-8">{{ $leve->campagne->libelle }}</dd>
                <dt class="col-sm-4">Date du levé</dt>
                <dd class="col-sm-8">{{ $leve->date_leve->format('d/m/Y') }}</dd>
                <dt class="col-sm-4">Levé par</dt>
                <dd class="col-sm-8">{{ $leve->technicien->nom_complet }} ({{ $leve->technicien->matricule }})</dd>
                <dt class="col-sm-4">Commentaire</dt>
                <dd class="col-sm-8">{{ $leve->commentaire ?: '—' }}</dd>
            </dl>
        </div></div>

        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h6 mb-2">Fichiers de points</h2>
            <p class="text-muted mb-0">L'import des fichiers PIED et SEMIS.</p>
        </div></div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h6 mb-3">Résultat du calcul</h2>
            @if ($leve->volume !== null)
                <div class="p-3 rounded" style="background: var(--phos-expansion)">
                    <div class="small text-muted">Volume du tas</div>
                    <div class="fs-3 fw-bold">{{ number_format($leve->volume, 0, ',', ' ') }} m³</div>
                </div>
            @else
                <p class="text-muted mb-0">Pas encore calculé.</p>
            @endif
        </div></div>
    </div>
</div>
@endsection