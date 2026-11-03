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
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="text-muted small mb-1">Levés › {{ $leve ? 'Modifier' : 'Nouveau levé' }}</p>
        <h1 class="h4 mb-0">{{ $leve ? 'Levé du '.$leve->date_leve->format('d/m/Y') : 'Nouveau levé' }}</h1>
    </div>

    <div class="etapes">
        <div class="etape {{ $etape > 1 ? 'fait' : 'active' }}">
            <span class="pastille">{{ $etape > 1 ? '✓' : '1' }}</span> Informations
        </div>
        <span class="etape-trait"></span>
        <div class="etape {{ $etape > 2 ? 'fait' : ($etape === 2 ? 'active' : '') }}">
            <span class="pastille">{{ $etape > 2 ? '✓' : '2' }}</span> Fichiers de points
        </div>
        <span class="etape-trait"></span>
        <div class="etape {{ $etape === 3 ? 'active' : '' }}">
            <span class="pastille">3</span> Calcul du volume
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">

        {{-- Informations du levé --}}
        <div class="card mb-4"><div class="card-body p-4">
            <h2 class="h6 mb-3">Informations du levé</h2>

            <form id="form-infos" method="POST" action="{{ $leve ? route('leves.update', $leve) : route('leves.store') }}">
                @csrf
                @if ($leve) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="stock_id">Stock *</label>
                        <select id="stock_id" name="stock_id" class="form-select @error('stock_id') is-invalid @enderror" required>
                            <option value="">— Choisir —</option>
                            @foreach ($stocks as $s)
                                <option value="{{ $s->id }}" @selected((int) old('stock_id', $leve->stock_id ?? 0) === $s->id)>
                                    {{ $s->nom }} ({{ $s->typeMateriau->libelle }})
                                </option>
                            @endforeach
                        </select>
                        @error('stock_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Stock introuvable ? <a href="{{ route('stocks.create') }}">+ Créer un stock</a></div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="campagne">Campagne *</label>
                        <select id="campagne" name="campagne" class="form-select @error('campagne') is-invalid @enderror" required>
                            @foreach ($campagnes as $cle => $libelle)
                                <option value="{{ $cle }}" @selected(old('campagne', $campagneChoisie) === $cle)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                        @error('campagne') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="date_leve">Date du levé *</label>
                        <input id="date_leve" name="date_leve" type="date" max="{{ now()->format('Y-m-d') }}"
                               value="{{ old('date_leve', $leve ? $leve->date_leve->format('Y-m-d') : now()->format('Y-m-d')) }}"
                               class="form-control @error('date_leve') is-invalid @enderror" required>
                        @error('date_leve') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="commentaire">Commentaire (facultatif)</label>
                        <textarea id="commentaire" name="commentaire" rows="3" maxlength="1000"
                                  placeholder="Observations de terrain, conditions du levé…"
                                  class="form-control @error('commentaire') is-invalid @enderror">{{ old('commentaire', $leve->commentaire ?? '') }}</textarea>
                        @error('commentaire') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </form>
        </div></div>

        {{-- Fichiers de points --}}
        <div class="card mb-4"><div class="card-body p-4">
            <h2 class="h6 mb-1">Fichiers de points</h2>
            <p class="text-muted small mb-3">
                Déposez un fichier par zone;
                le séparateur est détecté automatiquement.
            </p>
            <div class="row g-3">
                @foreach ($zones as $type => $zone)
                    @include('leves._zone_depot', ['type' => $type, 'zone' => $zone, 'stat' => $zone['stat']])
                @endforeach
            </div>
        </div></div>

        {{-- Actions --}}
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('leves.index') }}" class="btn btn-link text-secondary">Annuler</a>
            <button type="submit" form="form-infos" class="btn btn-outline-primary">Enregistrer le brouillon</button>

            @if ($leve && $pret)
                <button type="submit" form="form-calcul"
                        class="btn {{ $leve->volume !== null ? 'btn-outline-primary' : 'btn-primary' }}">
                    {{ $leve->volume !== null ? 'Recalculer le volume' : 'Calculer le volume' }}
                </button>
            @else
                <button type="button" class="btn btn-primary" disabled>Calculer le volume</button>
            @endif

            @if ($leve && $leve->statut === \App\Enums\StatutLeve::Calcule)
                <button type="submit" form="form-validation" class="btn btn-primary"
                        onclick="return confirm('Valider ce levé ? Il ne pourra plus être modifié.')">
                    Valider et enregistrer le levé
                </button>
            @endif
        </div>

        @if ($leve)
            <form id="form-calcul" method="POST" action="{{ route('leves.calcul', $leve) }}">@csrf</form>
            <form id="form-validation" method="POST" action="{{ route('leves.valider', $leve) }}">@csrf</form>
        @endif
    </div>

    {{-- Récapitulatif --}}
    <div class="col-xl-4">
        <div class="card"><div class="card-body p-4">
            <h2 class="h6 mb-2">Récapitulatif</h2>
            <ul class="list-unstyled recap mb-3">
                @foreach ($recap as [$titre, $valeur, $attente])
                    <li>
                        <span class="pastille-recap {{ $valeur ? 'ok' : '' }}">{{ $valeur ? '✓' : '' }}</span>
                        <div>
                            <div class="fw-semibold">{{ $titre }}</div>
                            <div class="small {{ $valeur ? '' : 'text-muted' }}">{{ $valeur ?? $attente }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="rounded p-3 small" style="background: {{ $pret ? 'var(--phos-primary-100)' : 'var(--phos-accent-100)' }}">
                {{ $message }}
            </div>
        </div></div>
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

    input.addEventListener('change', function () {
        if (input.files.length) { form.submit(); }
    });

    if (zone) {
        ['dragenter', 'dragover'].forEach(function (nom) {
            zone.addEventListener(nom, function (e) { e.preventDefault(); zone.classList.add('survol'); });
        });
        ['dragleave', 'drop'].forEach(function (nom) {
            zone.addEventListener(nom, function (e) { e.preventDefault(); zone.classList.remove('survol'); });
        });
        zone.addEventListener('drop', function (e) {
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                form.submit();
            }
        });
    }
});
</script>
@endpush