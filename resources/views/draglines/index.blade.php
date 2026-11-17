@extends('template')
@section('title', 'Suivi des draglines')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .hover-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .hover-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.06)!important; }
    </style>
@endpush

@section('content')
@php
    $utilisateur = auth()->user();
    $estTechnicien = $utilisateur->aLeRole(\App\Enums\Role::Technicien);
    $estAdmin = $utilisateur->aLeRole(\App\Enums\Role::Administrateur);
    $ongletImport = $errors->import->any();
    $aujourdhui = now()->format('Y-m-d');
    $systemeSaisi = $ongletImport ? 'utm' : old('systeme', 'utm');
@endphp

{{-- En-tête épuré et moderne (Harmonisé avec le Dashboard et l'Historique) --}}
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="text-muted small mb-1">WebSIG ICS · Sécurité & Trajectoires</p>
        <h1 class="h4 mb-0 fw-bold">Suivi des Draglines</h1>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted small bg-light px-3 py-1 rounded border">
            <i class="bi bi-shield-exclamation text-warning me-1"></i> Seuil d'alerte : ≤ 5m
        </span>
        @if ($estAdmin)
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-ajout">
                <i class="bi bi-plus-lg me-1"></i> Ajouter une dragline
            </button>
        @endif
    </div>
</div>

@if ($draglines->isEmpty())
    <div class="alert alert-light border shadow-sm">
        Aucune dragline enregistrée dans le système.
        @if ($estAdmin) Utilisez « Ajouter une dragline » pour créer la première. @endif
    </div>
@else
    {{-- Onglets de sélection des draglines --}}
    <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded shadow-sm border-0">
        @foreach ($draglines as $d)
            <li class="nav-item">
                <a class="nav-link fw-semibold {{ $active->id === $d->id ? 'bg-success text-white shadow-sm' : 'text-dark bg-light' }} px-4 py-2"
                   href="{{ route('draglines.index', ['dragline' => $d->id]) }}">
                   <i class="bi bi-truck-flatbed me-1"></i> {{ $d->nom }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="row g-4">
        {{-- Colonne principale : Carte de trajectoire & Tableau --}}
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center p-3 pb-2 border-bottom">
                        <h2 class="h6 mb-0 fw-bold"><i class="bi bi-map text-primary me-2"></i> Trajectoire — {{ $active->nom }}</h2>
                        @if ($estAdmin)
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-nom">
                                <i class="bi bi-pencil me-1"></i> Renommer
                            </button>
                        @endif
                    </div>
                    @if (count($points) === 0)
                        <div class="p-4 text-muted small text-center">Aucune position enregistrée pour cette dragline.</div>
                    @endif
                    <div id="carte-dragline" style="height: 380px"></div>
                </div>
            </div>

            {{-- Tableau Historique --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-table text-success me-2"></i> Historique des positions et analyse des passes</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th class="text-end">Latitude (WGS84)</th>
                                <th class="text-end">Longitude (WGS84)</th>
                                <th class="text-center">Passe / Profils</th>
                                <th class="text-end">Dist. Limite</th>
                                <th class="text-end pe-4">Statut / Alerte</th>
                                @if ($estTechnicien) <th class="text-end pe-4">Actions</th> @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($historique as $p)
                                @php($estProche = $p->localisation?->statut === 'proche')
                                <tr data-position="{{ $p->id }}" class="{{ $estProche ? 'table-danger bg-opacity-10' : '' }}" style="cursor: pointer" title="Cliquez pour centrer sur la carte">
                                    <td class="ps-4 fw-semibold">{{ $p->date_mesure->format('d/m/Y') }}</td>
                                    
                                    <td class="text-end font-monospace small" title="UTM X : {{ number_format($p->x, 2, ',', ' ') }} m">
                                        {{ number_format($p->lat, 6, ',', '') }}° N
                                    </td>
                                    <td class="text-end font-monospace small" title="UTM Y : {{ number_format($p->y, 2, ',', ' ') }} m">
                                        {{ number_format($p->lon, 6, ',', '') }}° O
                                    </td>

                                    <td class="text-center">
                                        @if($p->localisation?->passe)
                                            <span class="badge bg-light text-dark border">
                                                Passe {{ $p->localisation->passe }} 
                                                @if($p->localisation->pf_de)
                                                    (PF{{ $p->localisation->pf_de }}-PF{{ $p->localisation->pf_a }})
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>

                                    <td class="text-end fw-semibold">
                                        @if($p->localisation?->limite !== null)
                                            <span class="{{ $estProche ? 'text-danger' : 'text-dark' }}">
                                                {{ $p->localisation->limite }} m
                                            </span>
                                        @else
                                            <span class="text-muted small">Hors plan</span>
                                        @endif
                                    </td>

                                    <td class="text-end pe-4">
                                        @if($p->localisation?->limite !== null)
                                            @if($estProche)
                                                <span class="badge text-bg-danger px-2 py-1"><i class="bi bi-exclamation-triangle me-1"></i>Proche (≤5m)</span>
                                            @else
                                                <span class="badge text-bg-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Normal</span>
                                            @endif
                                        @else
                                            <span class="badge text-bg-secondary px-2 py-1">Hors des passes</span>
                                        @endif
                                    </td>
                                    
                                    @if ($estTechnicien)
                                        <td class="text-end pe-4">
                                            <form method="POST" action="{{ route('draglines.positions.destroy', $p) }}" class="d-inline"
                                                  onsubmit="return confirm('Supprimer cette position ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger border-0" title="Supprimer"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ $estTechnicien ? 7 : 6 }}" class="text-center text-muted py-4">Aucune position enregistrée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-muted small py-3 px-4 border-top">
                    <i class="bi bi-info-circle me-1 text-primary"></i> Coordonnées affichées en WGS84 (valeurs UTM d'origine en infobulle au survol).
                </div>
            </div>
        </div>

        {{-- Colonne de droite : Résumé & Enregistrement --}}
        <div class="col-xl-4">
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm border-start border-primary border-4">
                        <div class="card-body py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted fw-semibold text-uppercase">Dernière position</div>
                                <div class="fs-5 fw-bold text-dark mt-1">{{ $resume['derniere'] ? $resume['derniere']->format('d/m/Y') : '—' }}</div>
                            </div>
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle fs-5 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm border-start border-info border-4">
                        <div class="card-body py-3">
                            <div class="small text-muted fw-semibold text-uppercase">Total Positions</div>
                            <div class="fs-5 fw-bold text-dark mt-1">{{ $resume['nb'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm border-start border-success border-4">
                        <div class="card-body py-3">
                            <div class="small text-muted fw-semibold text-uppercase">Distance</div>
                            <div class="fs-5 fw-bold text-success mt-1">{{ number_format($resume['distance'], 0, ',', ' ') }} m</div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($estTechnicien)
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h6 mb-3 fw-bold"><i class="bi bi-plus-circle text-primary me-1"></i> Enregistrer une position</h2>

                        <ul class="nav nav-pills nav-fill mb-3 bg-light p-1 rounded" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link {{ $ongletImport ? '' : 'active py-2' }}" data-bs-toggle="tab" data-bs-target="#onglet-manuel">Saisie manuelle</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link {{ $ongletImport ? 'active py-2' : '' }}" data-bs-toggle="tab" data-bs-target="#onglet-import">Importer</button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade {{ $ongletImport ? '' : 'show active' }}" id="onglet-manuel">
                                <form method="POST" action="{{ route('draglines.positions.store', $active) }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold" for="m-date">Date *</label>
                                        <input id="m-date" name="date_mesure" type="date" max="{{ $aujourdhui }}"
                                               value="{{ $ongletImport ? $aujourdhui : old('date_mesure', $aujourdhui) }}"
                                               class="form-control form-control-sm" required>
                                    </div>

                                    <div class="btn-group btn-group-sm w-100 mb-3" role="group">
                                        <input type="radio" class="btn-check" name="systeme" id="sys-utm" value="utm" {{ $systemeSaisi === 'utm' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="sys-utm">UTM 28N (m)</label>
                                        <input type="radio" class="btn-check" name="systeme" id="sys-wgs84" value="wgs84" {{ $systemeSaisi === 'wgs84' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="sys-wgs84">WGS84 (°)</label>
                                    </div>

                                    <div id="champs-utm" class="{{ $systemeSaisi === 'utm' ? '' : 'd-none' }}">
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold" for="m-x">X (m) *</label>
                                                <input id="m-x" name="x" placeholder="301543,93" value="{{ $systemeSaisi === 'utm' ? old('x') : '' }}" class="form-control form-control-sm" {{ $systemeSaisi === 'utm' ? '' : 'disabled' }}>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold" for="m-y">Y (m) *</label>
                                                <input id="m-y" name="y" placeholder="1669668,19" value="{{ $systemeSaisi === 'utm' ? old('y') : '' }}" class="form-control form-control-sm" {{ $systemeSaisi === 'utm' ? '' : 'disabled' }}>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="champs-wgs84" class="{{ $systemeSaisi === 'wgs84' ? '' : 'd-none' }}">
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold" for="m-lat">Latitude *</label>
                                                <input id="m-lat" name="lat" placeholder="15,0629" value="{{ $systemeSaisi === 'wgs84' ? old('lat') : '' }}" class="form-control form-control-sm" {{ $systemeSaisi === 'wgs84' ? '' : 'disabled' }}>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold" for="m-lon">Longitude *</label>
                                                <input id="m-lon" name="lon" placeholder="-16,8210" value="{{ $systemeSaisi === 'wgs84' ? old('lon') : '' }}" class="form-control form-control-sm" {{ $systemeSaisi === 'wgs84' ? '' : 'disabled' }}>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-success btn-sm w-100 fw-semibold shadow-sm">Enregistrer la position</button>
                                </form>
                            </div>

                            <div class="tab-pane fade {{ $ongletImport ? 'show active' : '' }}" id="onglet-import">
                                <form method="POST" action="{{ route('draglines.positions.import', $active) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold" for="i-date">Date *</label>
                                        <input id="i-date" name="date_mesure" type="date" max="{{ $aujourdhui }}" value="{{ $aujourdhui }}" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold" for="i-fichier">Fichier (TXT/CSV) *</label>
                                        <input id="i-fichier" name="fichier" type="file" accept=".txt,.csv" class="form-control form-control-sm" required>
                                    </div>
                                    <button class="btn btn-success btn-sm w-100 fw-semibold shadow-sm">Importer</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif

{{-- Modales Admin --}}
@if ($estAdmin)
    <div class="modal fade" id="modal-ajout" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" action="{{ route('draglines.store') }}">
                @csrf
                <div class="modal-header"><h2 class="modal-title h5">Ajouter une dragline</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><label class="form-label small fw-semibold">Nom</label><input name="nom" class="form-control" maxlength="50" required></div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary btn-sm">Ajouter</button></div>
            </form>
        </div></div>
    </div>

    @if ($active)
        <div class="modal fade" id="modal-nom" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">
                <form method="POST" action="{{ route('draglines.update', $active) }}">
                    @csrf @method('PUT')
                    <div class="modal-header"><h2 class="modal-title h5">Renommer</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><label class="form-label small fw-semibold">Nom</label><input name="nom" class="form-control" maxlength="50" value="{{ $active->nom }}" required></div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary btn-sm">Enregistrer</button></div>
                </form>
            </div></div>
        </div>
    @endif
@endif
@endsection

@if ($active)
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        window.PHOSTRACK_DRAGLINE = { nom: @json($active->nom), points: @json($points) };
    </script>
    <script src="{{ asset('js/draglines.js') }}?v={{ filemtime(public_path('js/draglines.js')) }}"></script>
    @endpush
@endif