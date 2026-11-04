@extends('template')
@section('title', 'Carte interactive')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
<div id="carte-erreur" class="alert alert-danger d-none">Impossible de charger les données de la carte.</div>

<div class="carte-page">

    {{-- Panneau gauche : couches --}}
    <aside class="carte-lateral gauche">
        <h2 class="h6 mb-3">Couches</h2>

        <div class="small">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="c-stocks" checked>
                <label class="form-check-label fw-semibold" for="c-stocks">Stocks</label>
            </div>
            <div id="arbre-stocks" class="sous"></div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="c-zones" checked>
                <label class="form-check-label fw-semibold" for="c-zones">Zones</label>
            </div>
            <div id="arbre-zones" class="sous"></div>
        </div>

        <hr>

        <h2 class="h6 mb-2">Fond de carte</h2>
        <div class="form-check small">
            <input class="form-check-input" type="radio" name="fond" id="fond-satellite" value="satellite" checked>
            <label class="form-check-label" for="fond-satellite">Satellite</label>
        </div>
        <div class="form-check small">
            <input class="form-check-input" type="radio" name="fond" id="fond-plan" value="plan">
            <label class="form-check-label" for="fond-plan">Plan (OpenStreetMap)</label>
        </div>
    </aside>

    {{-- Carte --}}
    <div class="carte-centre">
        <div id="carte"></div>
        <div id="carte-coords" class="carte-coords">X : — &nbsp; Y : —</div>
    </div>

    {{-- Panneau droit : détail du stock (affiché seulement au clic sur un stock) --}}
    <aside class="carte-lateral droite">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h2 class="h6 mb-0">Détail du stock</h2>
            <button type="button" class="btn-close" id="d-fermer" aria-label="Fermer"></button>
        </div>

        <div id="detail">
            <div class="h5 mb-1" id="d-nom"></div>
            <span class="badge text-bg-secondary mb-3" id="d-type"></span>

            <dl class="row small mb-3 gy-1">
                <dt class="col-7 fw-normal text-muted">Dernier levé</dt>
                <dd class="col-5 text-end fw-semibold mb-0" id="d-date"></dd>
                <dt class="col-7 fw-normal text-muted">Nombre de levés</dt>
                <dd class="col-5 text-end fw-semibold mb-0" id="d-nb"></dd>
            </dl>

            <div class="rounded p-3 mb-3" style="background: var(--phos-primary-100)">
                <div class="small text-muted">Volume actuel</div>
                <div class="fs-3 fw-bold" style="color: var(--phos-primary-700)" id="d-volume"></div>
            </div>

            <a id="d-lien" href="#" class="btn btn-outline-primary w-100">Voir le levé</a>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/proj4@2.9.2/dist/proj4.js"></script>
<script>
    window.PHOSTRACK = {
        urlDonnees: @json(route('carte.donnees')),
        urlLeve: @json(route('leves.show', ['leve' => '__ID__']))
    };
</script>
<script src="{{ asset('js/carte.js') }}?v={{ filemtime(public_path('js/carte.js')) }}"></script>
@endpush