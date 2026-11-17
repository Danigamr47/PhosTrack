@extends('template')
@section('title', $leve ? 'Modifier le levé' : 'Nouveau levé')

@php
    $pied = $stats->get('pied');
    $semis = $stats->get('semis');
    $pret = $pied && $semis;
    $etape = ! $leve ? 1 : ($pret ? 3 : 2);

    $zones = [
        'pied' => ['titre' => 'Points de PIED', 'sous' => 'Pied du tas — sol naturel', 'fichier' => 'PIED.txt', 'stat' => $pied],
        'semis' => ['titre' => 'Points de SEMIS', 'sous' => 'Surface du tas — points semés', 'fichier' => 'SEMIS.txt', 'stat' => $semis],
    ];

    $recap = [
        ['Stock', $leve?->stock->nom, 'À renseigner'],
        ['Date du levé', $leve?->date_leve->format('d/m/Y'), 'À renseigner'],
        ['Fichier PIED', $pied ? number_format($pied->n, 0, ',', ' ').' points' : null, 'En attente du fichier'],
        ['Fichier SEMIS', $semis ? number_format($semis->n, 0, ',', ' ').' points' : null, 'En attente du fichier'],
    ];

    if (! $leve) {
        $message = 'Enregistrez le brouillon pour déposer les fichiers de points.';
    } elseif (! $pied && ! $semis) {
        $message = 'Ajoutez les fichiers PIED et SEMIS pour lancer le calcul.';
    } elseif (! $pied) {
        $message = 'Ajoutez le fichier PIED pour lancer le calcul.';
    } elseif (! $semis) {
        $message = 'Ajoutez le fichier SEMIS pour lancer le calcul.';
    } elseif ($leve->volume !== null) {
        $message = 'Volume calculé. Vérifiez le résultat, puis validez le levé.';
    } else {
        $message = 'Les deux fichiers sont chargés : vous pouvez calculer le volume.';
    }
@endphp

@section('content')
{{-- En-tête épuré et moderne harmonisé --}}
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="text-muted small mb-1">WebSIG ICS · Gestion topographique</p>
        <h1 class="h4 mb-0 fw-bold">{{ $leve ? 'Modifier le levé du '.$leve->date_leve->format('d/m/Y') : 'Nouveau levé topographique' }}</h1>
    </div>

    {{-- Stepper d'étapes visuel et soigné --}}
    <div class="d-flex align-items-center gap-2 bg-white px-3 py-2 rounded shadow-sm border small">
        <div class="d-flex align-items-center gap-1 {{ $etape >= 1 ? 'fw-bold text-primary' : 'text-muted' }}">
            <span class="badge {{ $etape > 1 ? 'text-bg-success' : 'text-bg-primary' }} rounded-circle p-1" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem;">{{ $etape > 1 ? '✓' : '1' }}</span> Infos
        </div>
        <span class="text-muted">›</span>
        <div class="d-flex align-items-center gap-1 {{ $etape >= 2 ? 'fw-bold text-primary' : 'text-muted' }}">
            <span class="badge {{ $etape > 2 ? 'text-bg-success' : ($etape === 2 ? 'text-bg-primary' : 'text-bg-secondary') }} rounded-circle p-1" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem;">{{ $etape > 2 ? '✓' : '2' }}</span> Fichiers
        </div>
        <span class="text-muted">›</span>
        <div class="d-flex align-items-center gap-1 {{ $etape === 3 ? 'fw-bold text-primary' : 'text-muted' }}">
            <span class="badge {{ $etape === 3 ? 'text-bg-primary' : 'text-bg-secondary' }} rounded-circle p-1" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem;">3</span> Calcul
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">

        {{-- Informations du levé --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 mb-3 fw-bold"><i class="bi bi-info-circle text-primary me-2"></i> Informations générales</h2>

                <form id="form-infos" method="POST" action="{{ $leve ? route('leves.update', $leve) : route('leves.store') }}">
                    @csrf
                    @if ($leve) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="stock_id">Stock *</label>
                            <select id="stock_id" name="stock_id" class="form-select form-select-sm @error('stock_id') is-invalid @enderror" required>
                                <option value="">— Choisir —</option>
                                @foreach ($stocks as $s)
                                    <option value="{{ $s->id }}" @selected((int) old('stock_id', $leve->stock_id ?? 0) === $s->id)>
                                        {{ $s->nom }} ({{ $s->typeMateriau->libelle }})
                                    </option>
                                @endforeach
                            </select>
                            @error('stock_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text small">Introuvable ? <a href="{{ route('stocks.create') }}">Créer un stock</a></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="campagne">Campagne *</label>
                            <select id="campagne" name="campagne" class="form-select form-select-sm @error('campagne') is-invalid @enderror" required>
                                @foreach ($campagnes as $cle => $libelle)
                                    <option value="{{ $cle }}" @selected(old('campagne', $campagneChoisie) === $cle)>{{ $libelle }}</option>
                                @endforeach
                            </select>
                            @error('campagne') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="date_leve">Date du levé *</label>
                            <input id="date_leve" name="date_leve" type="date" max="{{ now()->format('Y-m-d') }}"
                                   value="{{ old('date_leve', $leve ? $leve->date_leve->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                   class="form-control form-control-sm @error('date_leve') is-invalid @enderror" required>
                            @error('date_leve') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold" for="commentaire">Commentaire de terrain (facultatif)</label>
                            <textarea id="commentaire" name="commentaire" rows="2" maxlength="1000"
                                      placeholder="Observations, conditions topographiques…"
                                      class="form-control form-control-sm @error('commentaire') is-invalid @enderror">{{ old('commentaire', $leve->commentaire ?? '') }}</textarea>
                            @error('commentaire') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Fichiers de points --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 mb-1 fw-bold"><i class="bi bi-cloud-arrow-up text-success me-2"></i> Fichiers de points topographiques</h2>
                <p class="text-muted small mb-3">
                    Déposez les fichiers de points (séparateur détecté automatiquement) :
                </p>
                <div class="row g-3">
                    @foreach ($zones as $type => $zone)
                        @include('leves._zone_depot', ['type' => $type, 'zone' => $zone, 'stat' => $zone['stat']])
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Barre d'actions hiérarchisée --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <a href="{{ route('leves.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i> Annuler
                </a>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="submit" form="form-infos" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-save me-1"></i> Enregistrer le brouillon
                    </button>

                    @if ($leve && $pret)
                        <button type="submit" form="form-calcul"
                                class="btn {{ $leve->volume !== null ? 'btn-outline-success' : 'btn-success' }} btn-sm shadow-sm">
                            <i class="bi bi-calculator me-1"></i> {{ $leve->volume !== null ? 'Recalculer le volume' : 'Calculer le volume' }}
                        </button>
                    @else
                        <button type="button" class="btn btn-secondary btn-sm" disabled title="Chargez les deux fichiers PIED et SEMIS">
                            <i class="bi bi-calculator me-1"></i> Calculer le volume
                        </button>
                    @endif

                    @if ($leve && $leve->statut === \App\Enums\StatutLeve::Calcule)
                        <button type="submit" form="form-validation" class="btn btn-primary btn-sm shadow-sm"
                                onclick="return confirm('Valider ce levé ? Il sera verrouillé et ne pourra plus être modifié.')">
                            <i class="bi bi-check2-all me-1"></i> Valider et enregistrer
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if ($leve)
            <form id="form-calcul" method="POST" action="{{ route('leves.calcul', $leve) }}">@csrf</form>
            <form id="form-validation" method="POST" action="{{ route('leves.valider', $leve) }}">@csrf</form>
        @endif
    </div>

    {{-- Récapitulatif contextuel latéral --}}
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 mb-3 fw-bold"><i class="bi bi-check2-square text-info me-2"></i> État d'avancement</h2>
                <ul class="list-unstyled recap mb-3">
                    @foreach ($recap as [$titre, $valeur, $attente])
                        <li class="d-flex align-items-center gap-3 py-2 border-bottom border-light">
                            <span class="badge {{ $valeur ? 'text-bg-success' : 'text-bg-secondary' }} rounded-circle p-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">{{ $valeur ? '✓' : '•' }}</span>
                            <div>
                                <div class="fw-semibold small">{{ $titre }}</div>
                                <div class="small text-muted">{{ $valeur ?? $attente }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="alert {{ $pret ? 'alert-success' : 'alert-warning' }} small mb-0 p-3">
                    <i class="bi bi-info-circle me-1"></i> {{ $message }}
                </div>
            </div>
        </div>

        @if ($leve && $leve->volume !== null)
            @include('leves._resultat')
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.form-depot').forEach(function (form) {
    var input = form.querySelector('input[type=file]');
    var zone = form.querySelector('.zone-depot');

    if (input) {
        input.addEventListener('change', function () {
            if (input.files.length) { form.submit(); }
        });
    }

    if (zone) {
        ['dragenter', 'dragover'].forEach(function (nom) {
            zone.addEventListener(nom, function (e) { e.preventDefault(); zone.classList.add('survol'); });
        });
        ['dragleave', 'drop'].forEach(function (nom) {
            zone.addEventListener(nom, function (e) { e.preventDefault(); zone.classList.remove('survol'); });
        });
        zone.addEventListener('drop', function (e) {
            if (e.dataTransfer.files.length && input) {
                input.files = e.dataTransfer.files;
                form.submit();
            }
        });
    }
});
</script>
@endpush