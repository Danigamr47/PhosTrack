@php
    $apercu = session('apercu.type') === $type ? session('apercu') : null;
    $erreurs = session('erreurs_fichier.type') === $type ? session('erreurs_fichier.messages') : [];
    $ignorees = collect($apercu['ignorees'] ?? []);
@endphp

<div class="col-md-6">
    <div class="border rounded-3 p-3 h-100 bg-white">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="fw-semibold">{{ $zone['titre'] }}</div>
                <div class="small text-muted">{{ $zone['sous'] }}</div>
            </div>
            <span class="badge text-bg-{{ $stat ? 'success' : 'secondary' }}">{{ $stat ? 'Chargé' : 'En attente' }}</span>
        </div>

        @if ($erreurs)
            <div class="alert alert-danger py-2 small">
                <ul class="mb-0 ps-3">
                    @foreach ($erreurs as $message) <li>{{ $message }}</li> @endforeach
                </ul>
            </div>
        @endif

        @if (! $leve)
            <div class="zone-depot desactive">
                <i class="bi bi-lock fs-4"></i>
                <div class="small mt-2">Enregistrez d'abord le brouillon pour déposer ce fichier.</div>
            </div>

        @elseif (! $stat)
            <form method="POST" action="{{ route('leves.points.store', [$leve, $type]) }}"
                  enctype="multipart/form-data" class="form-depot">
                @csrf
                <label class="zone-depot mb-0">
                    <i class="bi bi-cloud-arrow-up fs-3"></i>
                    <div class="fw-semibold mt-1">Glissez-déposez {{ $zone['fichier'] }} ici</div>
                    <div class="small text-muted">ou cliquez pour parcourir</div>
                    <div class="small text-muted mt-2">TXT ou CSV · colonnes ID, X, Y, Z</div>
                    <input type="file" name="fichier" accept=".txt,.csv,text/plain,text/csv" class="d-none">
                </label>
            </form>

        @else
            <div class="fichier-charge p-3 mb-3 d-flex align-items-center gap-3">
                <span class="icone-fichier">TXT</span>
                <div>
                    <div class="fw-semibold">{{ $apercu['fichier'] ?? 'Fichier chargé' }}</div>
                    <div class="small text-muted">{{ number_format($stat->n, 0, ',', ' ') }} points</div>
                </div>
            </div>

            <ul class="list-unstyled small mb-3">
                <li class="mb-1"><span class="point-controle"></span>{{ number_format($stat->n, 0, ',', ' ') }} points lus</li>
                <li class="mb-1"><span class="point-controle"></span>Altitude Z : {{ number_format($stat->zmin, 2, ',', ' ') }} – {{ number_format($stat->zmax, 2, ',', ' ') }} m</li>
                @if ($apercu)
                    <li class="mb-1"><span class="point-controle"></span>Séparateur détecté : {{ $apercu['separateur'] }}</li>
                    @if ($apercu['entete'])
                        <li class="mb-1"><span class="point-controle"></span>En-tête détecté et ignoré</li>
                    @endif
                    @if ($ignorees->isNotEmpty())
                        <li class="mb-1">
                            <span class="point-controle alerte"></span>
                            {{ $ignorees->count() }} ligne(s) ignorée(s) : {{ $ignorees->pluck('raison')->unique()->implode(' ; ') }}
                            (lignes {{ $ignorees->pluck('ligne')->take(8)->implode(', ') }}{{ $ignorees->count() > 8 ? '…' : '' }})
                        </li>
                    @endif
                @endif
            </ul>

            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('leves.points.store', [$leve, $type]) }}"
                      enctype="multipart/form-data" class="form-depot">
                    @csrf
                    <label class="btn btn-outline-primary btn-sm mb-0">
                        Remplacer le fichier
                        <input type="file" name="fichier" accept=".txt,.csv,text/plain,text/csv" class="d-none">
                    </label>
                </form>
                <form method="POST" action="{{ route('leves.points.destroy', [$leve, $type]) }}"
                      onsubmit="return confirm('Retirer ce fichier ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm">Retirer</button>
                </form>
            </div>
        @endif
    </div>
</div>