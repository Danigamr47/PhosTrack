@extends('template')
@section('title', 'Détail du levé')

@section('content')
@php
    $estTechnicien = auth()->user()->aLeRole(\App\Enums\Role::Technicien);
    $pied = $stats->get('pied');
    $semis = $stats->get('semis');
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <p class="text-muted small mb-1">Levés › Détail du relevé</p>
        <h1 class="h4 mb-0 fw-bold">
            {{ $leve->stock->nom }} — {{ $leve->date_leve->format('d/m/Y') }}
            <span class="badge text-bg-{{ $leve->statut->couleur() }} ms-2 px-2 py-1">{{ $leve->statut->libelle() }}</span>
        </h1>
    </div>
    
    <div class="d-flex gap-2">
        <a href="{{ route('historique.index', ['stock_id' => $leve->stock_id]) }}" class="btn btn-outline-success btn-sm shadow-sm">
            <i class="bi bi-clock-history me-1"></i> Explorer l'historique du stock
        </a>
        <a href="{{ route('leves.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
        @if ($estTechnicien && ! $leve->estValide())
            <a href="{{ route('leves.edit', $leve) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil me-1"></i> Modifier
            </a>
        @endif
    </div>
</div>

<div class="row g-4">
    {{-- Colonne de gauche : Informations de terrain & Fichiers sources --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 mb-3 fw-bold"><i class="bi bi-info-circle text-primary me-2"></i> Informations générales</h2>
                <dl class="row mb-0 gy-2">
                    <dt class="col-sm-4 text-muted small fw-normal">Stock concerné</dt>
                    <dd class="col-sm-8 fw-semibold">
                        {{ $leve->stock->nom }} ({{ $leve->stock->typeMateriau->libelle }})
                        <a href="{{ route('historique.index', ['stock_id' => $leve->stock_id]) }}" class="ms-2 small text-decoration-none">
                            <i class="bi bi-box-arrow-up-right"></i> Voir la courbe
                        </a>
                    </dd>

                    <dt class="col-sm-4 text-muted small fw-normal">Campagne rattachée</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $leve->campagne->libelle }}</dd>

                    <dt class="col-sm-4 text-muted small fw-normal">Date effective du levé</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $leve->date_leve->format('d/m/Y') }}</dd>

                    <dt class="col-sm-4 text-muted small fw-normal">Opérateur / Technicien</dt>
                    <dd class="col-sm-8 fw-semibold">
                        {{ $leve->technicien->nom_complet }} 
                        <span class="text-muted small">({{ $leve->technicien->matricule }})</span>
                    </dd>

                    <dt class="col-sm-4 text-muted small fw-normal">Commentaire de terrain</dt>
                    <dd class="col-sm-8 text-dark fst-italic bg-light p-2 rounded mb-0">
                        {{ $leve->commentaire ?: 'Aucun commentaire particulier renseigné pour ce levé.' }}
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h6 mb-3 fw-bold"><i class="bi bi-file-earmark-text text-secondary me-2"></i> Traçabilité des fichiers sources</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold small">Points de PIED (Sol)</span>
                                <span class="badge {{ $pied ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $pied ? number_format($pied->n, 0, ',', ' ') . 'pts' : 'Absent' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold small">Points de SEMIS (Tas)</span>
                                <span class="badge {{ $semis ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $semis ? number_format($semis->n, 0, ',', ' ') . 'pts' : 'Absent' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Colonne de droite : Résultats topographiques précis (Via ton composant _resultat) --}}
    <div class="col-lg-4">
        @include('leves._resultat')
    </div>
</div>
@endsection