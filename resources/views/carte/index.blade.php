@extends('template')
@section('title', 'Carte interactive')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
<div id="carte-erreur" class="alert alert-danger d-none">Impossible de charger les données de la carte.</div>

<div class="carte-page">

    {{-- Panneau gauche : recherche, couches, filtres, outils --}}
    <aside class="carte-lateral gauche">

        <label class="form-label small fw-semibold" for="recherche">Rechercher un stock</label>
        <input id="recherche" list="liste-stocks" class="form-control form-control-sm"
               placeholder="Nom du stock…" autocomplete="off">
        <datalist id="liste-stocks"></datalist>
        <div id="recherche-msg" class="form-text text-danger"></div>

        <h2 class="h6 mt-4 mb-3">Couches</h2>
        <div class="small">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="c-stocks" checked>
                <label class="form-check-label fw-semibold" for="c-stocks">Stocks</label>
            </div>
            <div id="arbre-stocks" class="sous"></div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="c-draglines" checked>
                <label class="form-check-label fw-semibold" for="c-draglines">
                    <span class="pastille-couleur" style="background: #E07B1F"></span> Draglines
                </label>
            </div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="c-zones" checked>
                <label class="form-check-label fw-semibold" for="c-zones">Zones</label>
            </div>
            <div id="arbre-zones" class="sous"></div>

        </div>

        <hr>

        <h2 class="h6 mb-2">Filtres</h2>
        <label class="form-label small" for="filtre-campagne">Campagne</label>
        <select id="filtre-campagne" class="form-select form-select-sm">
            <option value="">Dernier levé validé</option>
        </select>

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
        <div id="carte-coords" class="carte-coords" title="Clic droit sur la carte pour copier les coordonnées d'un point">X : — &nbsp; Y : — &nbsp; · clic droit : copier</div>
    </div>

    {{-- Panneau droit : détail du stock (affiché seulement au clic sur un stock) --}}
    <aside class="carte-lateral droite">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h2 class="h6 mb-0">Détail du stock</h2>
            <button type="button" class="btn-close" id="d-fermer" aria-label="Fermer"></button>
        </div>

        <div class="h5 mb-1" id="d-nom"></div>
        <span class="badge text-bg-secondary mb-3" id="d-type"></span>

        <dl class="row small mb-3 gy-1">
            <dt class="col-5 fw-normal text-muted">Zone</dt>
            <dd class="col-7 text-end fw-semibold mb-0" id="d-zone"></dd>
            <dt class="col-6 fw-normal text-muted" id="d-etiq-date">Dernier levé</dt>
            <dd class="col-6 text-end fw-semibold mb-0"><a href="#" id="d-date" class="text-reset"></a></dd>
            <dt class="col-7 fw-normal text-muted">Nombre de levés</dt>
            <dd class="col-5 text-end fw-semibold mb-0" id="d-nb"></dd>
        </dl>

        <div class="rounded p-3 mb-3" style="background: var(--phos-primary-100)">
            <div class="small text-muted" id="d-etiq-volume">Volume actuel</div>
            <div class="fs-3 fw-bold" style="color: var(--phos-primary-700)" id="d-volume"></div>
            <div class="small" id="d-variation"></div>
        </div>

        <h3 class="h6">Évolution du volume</h3>
        <div class="mb-3" style="height: 150px"><canvas id="d-graphique"></canvas></div>

        <a id="d-historique" href="#" class="btn btn-outline-primary w-100 mb-4">Voir l'historique</a>

        <h3 class="h6">Télécharger les coordonnées</h3>

        <div class="btn-group btn-group-sm w-100 mb-2" role="group" aria-label="Système de coordonnées">
            <input type="radio" class="btn-check" name="srs" id="srs-utm" value="utm" checked>
            <label class="btn btn-outline-primary" for="srs-utm">UTM 28N (mètres)</label>
            <input type="radio" class="btn-check" name="srs" id="srs-wgs84" value="wgs84">
            <label class="btn btn-outline-primary" for="srs-wgs84">WGS84 (degrés)</label>
        </div>

        <div class="list-group list-group-flush small" id="d-exports">
            <a class="list-group-item list-group-item-action px-0" data-export="pied" href="#">
                <i class="bi bi-download me-2"></i>Points de PIED <span class="text-muted">(.txt)</span>
            </a>
            <a class="list-group-item list-group-item-action px-0" data-export="semis" href="#">
                <i class="bi bi-download me-2"></i>Points de SEMIS <span class="text-muted">(.txt)</span>
            </a>
            <a class="list-group-item list-group-item-action px-0" data-export="tous" href="#">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i>Tous les points <span class="text-muted">(.csv, Excel)</span>
            </a>
            <a class="list-group-item list-group-item-action px-0" data-export="contour" href="#">
                <i class="bi bi-bounding-box me-2"></i>Contour du stock <span class="text-muted">(.geojson, WGS84)</span>
            </a>
        </div>
        <p class="text-muted mt-2 mb-0" style="font-size: .7rem">
            Le système choisi s'applique aux fichiers de points. Le contour GeoJSON est toujours en WGS84 (norme du format).
        </p>
    </aside>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/proj4@2.9.2/dist/proj4.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
    window.PHOSTRACK = {
        urlDonnees: @json(route('carte.donnees')),
        urlLeve: @json(route('leves.show', ['leve' => '__ID__'])),
        urlLeves: @json(url('leves')),
        urlHistorique: @json(route('leves.index')),
        urlDraglines: @json(route('draglines.index'))
    };
</script>
<script src="{{ asset('js/carte.js') }}?v={{ filemtime(public_path('js/carte.js')) }}"></script>
@endpush