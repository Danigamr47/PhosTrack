@extends('template')
@section('title', 'Suivi des draglines')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
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

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <p class="text-muted small mb-1">Draglines</p>
        <h1 class="h4 mb-0">Suivi des draglines</h1>
    </div>
    @if ($estAdmin)
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-ajout">
            <i class="bi bi-plus-lg"></i> Ajouter une dragline
        </button>
    @endif
</div>

@if ($draglines->isEmpty())
    <div class="alert alert-info">
        Aucune dragline enregistrée.
        @if ($estAdmin) Utilisez « Ajouter une dragline » pour créer la première. @endif
    </div>
@else
    <ul class="nav nav-tabs mb-4">
        @foreach ($draglines as $d)
            <li class="nav-item">
                <a class="nav-link {{ $active->id === $d->id ? 'active' : '' }}"
                   href="{{ route('draglines.index', ['dragline' => $d->id]) }}">{{ $d->nom }}</a>
            </li>
        @endforeach
    </ul>

    <div class="row g-4">

        {{-- Colonne principale : trajectoire et historique --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center p-3 pb-2">
                        <h2 class="h6 mb-0">Trajectoire — {{ $active->nom }}</h2>
                        @if ($estAdmin)
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-nom">
                                <i class="bi bi-pencil"></i> Renommer
                            </button>
                        @endif
                    </div>
                    @if (count($points) === 0)
                        <p class="text-muted small px-3 mb-2">Aucune position enregistrée pour cette dragline.</p>
                    @endif
                    <div id="carte-dragline" style="height: 380px"></div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th class="text-end">X (m)</th>
                                <th class="text-end">Y (m)</th>
                                <th class="text-end">Avancement (m)</th>
                                <th>Relevé par</th>
                                @if ($estTechnicien) <th class="text-end">Actions</th> @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($historique as $p)
                                <tr data-position="{{ $p->id }}" style="cursor: pointer">
                                    <td>{{ $p->date_mesure->format('d/m/Y') }}</td>
                                    <td class="text-end" title="WGS84 : latitude {{ number_format($p->lat, 6, ',', '') }} · longitude {{ number_format($p->lon, 6, ',', '') }}">{{ number_format($p->x, 2, ',', ' ') }}</td>
                                    <td class="text-end" title="WGS84 : latitude {{ number_format($p->lat, 6, ',', '') }} · longitude {{ number_format($p->lon, 6, ',', '') }}">{{ number_format($p->y, 2, ',', ' ') }}</td>
                                    <td class="text-end">{{ $p->avancement !== null ? number_format($p->avancement, 1, ',', ' ') : '—' }}</td>
                                    <td>{{ $p->technicien->matricule }}</td>
                                    @if ($estTechnicien)
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('draglines.positions.destroy', $p) }}" class="d-inline"
                                                  onsubmit="return confirm('Supprimer cette position ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Aucune position enregistrée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small px-3 py-2 mb-0 border-top">
                    L'avancement est la distance parcourue depuis la position précédente. Survolez X ou Y pour voir
                    la latitude et la longitude (WGS84). Les colonnes « Passe » et « Distance à la limite »
                    apparaîtront lorsque le plan des passes sera disponible.
                </p>
            </div>
        </div>

        {{-- Colonne de droite : résumé et saisie --}}
        <div class="col-xl-4">
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="card"><div class="card-body py-3">
                        <div class="small text-muted">Dernière position</div>
                        <div class="fs-5 fw-semibold">{{ $resume['derniere'] ? $resume['derniere']->format('d/m/Y') : '—' }}</div>
                    </div></div>
                </div>
                <div class="col-6">
                    <div class="card"><div class="card-body py-3">
                        <div class="small text-muted">Positions</div>
                        <div class="fs-5 fw-semibold">{{ $resume['nb'] }}</div>
                    </div></div>
                </div>
                <div class="col-6">
                    <div class="card"><div class="card-body py-3">
                        <div class="small text-muted">Distance parcourue</div>
                        <div class="fs-5 fw-semibold">{{ number_format($resume['distance'], 0, ',', ' ') }} m</div>
                    </div></div>
                </div>
            </div>

            @if ($estTechnicien)
                <div class="card"><div class="card-body p-4">
                    <h2 class="h6 mb-3">Enregistrer une position</h2>

                    <ul class="nav nav-pills nav-fill mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $ongletImport ? '' : 'active' }}" type="button" role="tab"
                                    data-bs-toggle="tab" data-bs-target="#onglet-manuel">Saisie manuelle</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $ongletImport ? 'active' : '' }}" type="button" role="tab"
                                    data-bs-toggle="tab" data-bs-target="#onglet-import">Importer un fichier</button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        {{-- Saisie manuelle --}}
                        <div class="tab-pane fade {{ $ongletImport ? '' : 'show active' }}" id="onglet-manuel" role="tabpanel">
                            <form method="POST" action="{{ route('draglines.positions.store', $active) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" for="m-date">Date de la position *</label>
                                    <input id="m-date" name="date_mesure" type="date" max="{{ $aujourdhui }}"
                                           value="{{ $ongletImport ? $aujourdhui : old('date_mesure', $aujourdhui) }}"
                                           class="form-control {{ $errors->manuelle->has('date_mesure') ? 'is-invalid' : '' }}" required>
                                    @if ($errors->manuelle->has('date_mesure'))
                                        <div class="invalid-feedback">{{ $errors->manuelle->first('date_mesure') }}</div>
                                    @endif
                                </div>

                                <div class="btn-group btn-group-sm w-100 mb-3" role="group" aria-label="Système de coordonnées">
                                    <input type="radio" class="btn-check" name="systeme" id="sys-utm" value="utm" {{ $systemeSaisi === 'utm' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary" for="sys-utm">UTM 28N (mètres)</label>
                                    <input type="radio" class="btn-check" name="systeme" id="sys-wgs84" value="wgs84" {{ $systemeSaisi === 'wgs84' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary" for="sys-wgs84">WGS84 (degrés)</label>
                                </div>

                                {{-- Coordonnées en mètres (UTM 28N) --}}
                                <div id="champs-utm" class="{{ $systemeSaisi === 'utm' ? '' : 'd-none' }}">
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label" for="m-x">X (m) *</label>
                                            <input id="m-x" name="x" inputmode="decimal" placeholder="301543,93"
                                                   value="{{ $systemeSaisi === 'utm' ? old('x') : '' }}"
                                                   class="form-control {{ $errors->manuelle->has('x') ? 'is-invalid' : '' }}"
                                                   {{ $systemeSaisi === 'utm' ? '' : 'disabled' }} required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label" for="m-y">Y (m) *</label>
                                            <input id="m-y" name="y" inputmode="decimal" placeholder="1669668,19"
                                                   value="{{ $systemeSaisi === 'utm' ? old('y') : '' }}"
                                                   class="form-control {{ $errors->manuelle->has('y') ? 'is-invalid' : '' }}"
                                                   {{ $systemeSaisi === 'utm' ? '' : 'disabled' }} required>
                                        </div>
                                        @if ($errors->manuelle->has('x'))
                                            <div class="col-12 text-danger small">{{ $errors->manuelle->first('x') }}</div>
                                        @endif
                                        @if ($errors->manuelle->has('y'))
                                            <div class="col-12 text-danger small">{{ $errors->manuelle->first('y') }}</div>
                                        @endif
                                        <div class="col-12 form-text">UTM 28N, en mètres. Virgule ou point acceptés.</div>
                                    </div>
                                </div>

                                {{-- Coordonnées angulaires (WGS84) --}}
                                <div id="champs-wgs84" class="{{ $systemeSaisi === 'wgs84' ? '' : 'd-none' }}">
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label" for="m-lat">Latitude *</label>
                                            <input id="m-lat" name="lat" placeholder="15,0629"
                                                   value="{{ $systemeSaisi === 'wgs84' ? old('lat') : '' }}"
                                                   class="form-control {{ $errors->manuelle->has('lat') ? 'is-invalid' : '' }}"
                                                   {{ $systemeSaisi === 'wgs84' ? '' : 'disabled' }} required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label" for="m-lon">Longitude *</label>
                                            <input id="m-lon" name="lon" placeholder="-16,8210"
                                                   value="{{ $systemeSaisi === 'wgs84' ? old('lon') : '' }}"
                                                   class="form-control {{ $errors->manuelle->has('lon') ? 'is-invalid' : '' }}"
                                                   {{ $systemeSaisi === 'wgs84' ? '' : 'disabled' }} required>
                                        </div>
                                        @if ($errors->manuelle->has('lat'))
                                            <div class="col-12 text-danger small">{{ $errors->manuelle->first('lat') }}</div>
                                        @endif
                                        @if ($errors->manuelle->has('lon'))
                                            <div class="col-12 text-danger small">{{ $errors->manuelle->first('lon') }}</div>
                                        @endif
                                        <div class="col-12 form-text">
                                            Degrés décimaux (négatif à l'ouest) ou degrés-minutes-secondes
                                            avec N, S, E, O : 15°03'46"N · 16°49'15"O. Convertis en UTM 28N à l'enregistrement.
                                        </div>
                                    </div>
                                </div>

                                <button class="btn btn-primary w-100" data-chargement="Enregistrement…">Enregistrer la position</button>
                            </form>
                        </div>

                        {{-- Import d'un fichier --}}
                        <div class="tab-pane fade {{ $ongletImport ? 'show active' : '' }}" id="onglet-import" role="tabpanel">
                            <form method="POST" action="{{ route('draglines.positions.import', $active) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" for="i-date">Date de la position *</label>
                                    <input id="i-date" name="date_mesure" type="date" max="{{ $aujourdhui }}"
                                           value="{{ $ongletImport ? old('date_mesure', $aujourdhui) : $aujourdhui }}"
                                           class="form-control {{ $errors->import->has('date_mesure') ? 'is-invalid' : '' }}" required>
                                    @if ($errors->import->has('date_mesure'))
                                        <div class="invalid-feedback">{{ $errors->import->first('date_mesure') }}</div>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="i-fichier">Fichier (TXT ou CSV) *</label>
                                    <input id="i-fichier" name="fichier" type="file" accept=".txt,.csv,text/plain,text/csv"
                                           class="form-control {{ $errors->import->has('fichier') ? 'is-invalid' : '' }}" required>
                                    @if ($errors->import->has('fichier'))
                                        <div class="invalid-feedback">{{ $errors->import->first('fichier') }}</div>
                                    @endif
                                    <div class="form-text">
                                        Une ligne par position : X et Y en UTM 28N. Un libellé avant les coordonnées
                                        et une altitude Z sont facultatifs. Le séparateur est détecté automatiquement.
                                    </div>
                                </div>
                                <button class="btn btn-primary w-100" data-chargement="Import en cours…">Importer la position</button>
                            </form>
                        </div>
                    </div>
                </div></div>
            @else
                <div class="card"><div class="card-body small text-muted">
                    Seul un Technicien peut enregistrer ou supprimer une position de dragline.
                </div></div>
            @endif
        </div>
    </div>
@endif

{{-- Fenêtres de l'Administrateur : ajouter / renommer une dragline --}}
@if ($estAdmin)
    <div class="modal fade" id="modal-ajout" tabindex="-1" aria-labelledby="titre-ajout" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" action="{{ route('draglines.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="titre-ajout">Ajouter une dragline</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="ajout-nom">Nom</label>
                    <input id="ajout-nom" name="nom" class="form-control" maxlength="50" placeholder="Ex. : 1250B" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div></div>
    </div>

    @if ($active)
        <div class="modal fade" id="modal-nom" tabindex="-1" aria-labelledby="titre-nom" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">
                <form method="POST" action="{{ route('draglines.update', $active) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="titre-nom">Renommer la dragline</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="nom-nom">Nom</label>
                        <input id="nom-nom" name="nom" class="form-control" maxlength="50" value="{{ $active->nom }}" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button class="btn btn-primary">Enregistrer</button>
                    </div>
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
        window.PHOSTRACK_DRAGLINE = {
            nom: @json($active->nom),
            points: @json($points)
        };
    </script>
    <script src="{{ asset('js/draglines.js') }}?v={{ filemtime(public_path('js/draglines.js')) }}"></script>
    @endpush
@endif