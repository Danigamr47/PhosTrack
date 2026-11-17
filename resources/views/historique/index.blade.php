@extends('template')
@section('title', 'Historique & Comparaison')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="text-muted small mb-1">Traçabilité & Suivi volumétrique</p>
        <h1 class="h4 mb-0 fw-bold">Historique et comparaison des stocks</h1>
    </div>
    <span class="text-muted small bg-light px-3 py-1 rounded border">
        <i class="bi bi-shield-check text-success me-1"></i> Isolation stricte par matériau
    </span>
</div>

{{-- 1. Barre de filtres multicritères --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('historique.index') }}" class="row g-3 align-items-end">
            {{-- Filtre Matériau --}}
            <div class="col-md-3">
                <label for="type_materiau_id" class="form-label small fw-semibold text-muted">Nature du matériau</label>
                <select name="type_materiau_id" id="type_materiau_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tous les matériaux</option>
                    @foreach($typesMateriaux as $tm)
                        <option value="{{ $tm->id }}" {{ $filtres['type_materiau_id'] == $tm->id ? 'selected' : '' }}>
                            {{ $tm->libelle }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre Stock (active le comparateur) --}}
            <div class="col-md-4">
                <label for="stock_id" class="form-label small fw-semibold text-muted">
                    Stock spécifique <span class="text-primary">(active la comparaison)</span>
                </label>
                <select name="stock_id" id="stock_id" class="form-select form-select-sm border-primary fw-semibold" onchange="this.form.submit()">
                    <option value="">— Sélectionner un stock pour comparer —</option>
                    @foreach($stocks as $s)
                        <option value="{{ $s->id }}" {{ $filtres['stock_id'] == $s->id ? 'selected' : '' }}>
                            {{ $s->nom }} ({{ $s->typeMateriau->libelle }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre Campagne --}}
            <div class="col-md-3">
                <label for="campagne_id" class="form-label small fw-semibold text-muted">Campagne</label>
                <select name="campagne_id" id="campagne_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Toutes les campagnes</option>
                    @foreach($campagnes as $c)
                        <option value="{{ $c->id }}" {{ $filtres['campagne_id'] == $c->id ? 'selected' : '' }}>
                            {{ $c->libelle }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Bouton Réinitialiser --}}
            <div class="col-md-2 d-flex gap-2">
                @if($filtres['stock_id'] || $filtres['campagne_id'] || $filtres['type_materiau_id'])
                    <a href="{{ route('historique.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
                    </a>
                @else
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel me-1"></i> Filtrer
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- 2. Module de Comparaison & Courbe d'évolution (Visible lorsqu'un stock est sélectionné) --}}
@if($comparaison)
    <div class="card border-0 shadow-sm mb-4 border-start border-4" style="border-left-color: {{ $comparaison->couleur }} !important;">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-circle d-inline-block shadow-sm" style="width: 12px; height: 12px; background: {{ $comparaison->couleur }};"></span>
                <h2 class="h6 mb-0 fw-bold">
                    Comparaison volumétrique : {{ $comparaison->stock->nom }}
                </h2>
                <span class="badge bg-light text-dark border">{{ $comparaison->stock->typeMateriau->libelle }}</span>
            </div>

            {{-- Sélecteur de deux levés précis du même stock --}}
            <form method="GET" action="{{ route('historique.index') }}" class="d-flex flex-wrap align-items-center gap-2 mb-0">
                <input type="hidden" name="stock_id" value="{{ $comparaison->stock->id }}">
                @if($filtres['campagne_id']) <input type="hidden" name="campagne_id" value="{{ $filtres['campagne_id'] }}"> @endif
                @if($filtres['type_materiau_id']) <input type="hidden" name="type_materiau_id" value="{{ $filtres['type_materiau_id'] }}"> @endif

                <span class="small text-muted">Confronter :</span>
                <select name="leve_b" class="form-select form-select-sm w-auto" onchange="this.form.submit()" title="Relevé de référence (ancien)">
                    @foreach($comparaison->leves_dispo as $ld)
                        <option value="{{ $ld->id }}" {{ $comparaison->ancien->id === $ld->id ? 'selected' : '' }}>
                            {{ $ld->date_leve->format('d/m/Y') }} ({{ number_format($ld->volume, 0, ',', ' ') }} m³)
                        </option>
                    @endforeach
                </select>
                <span class="small text-muted fw-bold">vs</span>
                <select name="leve_a" class="form-select form-select-sm w-auto" onchange="this.form.submit()" title="Relevé comparé (récent)">
                    @foreach($comparaison->leves_dispo as $ld)
                        <option value="{{ $ld->id }}" {{ $comparaison->recent->id === $ld->id ? 'selected' : '' }}>
                            {{ $ld->date_leve->format('d/m/Y') }} ({{ number_format($ld->volume, 0, ',', ' ') }} m³)
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                {{-- Cartes de synthèse Avant / Après / Delta --}}
                <div class="col-lg-5">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 rounded bg-light border h-100">
                                <div class="small text-muted mb-1">Relevé antérieur</div>
                                <div class="fw-bold fs-5 text-dark">{{ number_format($comparaison->ancien->volume, 1, ',', ' ') }} <span class="fs-6 fw-normal">m³</span></div>
                                <div class="small text-muted mt-1"><i class="bi bi-calendar3 me-1"></i>{{ $comparaison->ancien->date_leve->format('d/m/Y') }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-light border h-100">
                                <div class="small text-muted mb-1">Relevé comparé</div>
                                <div class="fw-bold fs-5 text-dark">{{ number_format($comparaison->recent->volume, 1, ',', ' ') }} <span class="fs-6 fw-normal">m³</span></div>
                                <div class="small text-muted mt-1"><i class="bi bi-calendar3 me-1"></i>{{ $comparaison->recent->date_leve->format('d/m/Y') }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            @php
                                $estPositif = $comparaison->delta > 0;
                                $estNegatif = $comparaison->delta < 0;
                            @endphp
                            <div class="p-3 rounded border {{ $estPositif ? 'bg-success bg-opacity-10 border-success' : ($estNegatif ? 'bg-danger bg-opacity-10 border-danger' : 'bg-light') }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="small text-muted fw-semibold text-uppercase">Différentiel volumétrique </div>
                                        <div class="fs-4 fw-bold {{ $estPositif ? 'text-success' : ($estNegatif ? 'text-danger' : 'text-dark') }}">
                                            {{ $estPositif ? '+' : '' }}{{ number_format($comparaison->delta, 1, ',', ' ') }} m³
                                        </div>
                                    </div>
                                    @if($comparaison->variation_pct !== null)
                                        <span class="badge fs-6 px-3 py-2 {{ $estPositif ? 'text-bg-success' : ($estNegatif ? 'text-bg-danger' : 'text-bg-secondary') }}">
                                            <i class="bi {{ $estPositif ? 'bi-graph-up-arrow' : ($estNegatif ? 'bi-graph-down-arrow' : 'bi-dash') }} me-1"></i>
                                            {{ $estPositif ? '+' : '' }}{{ number_format($comparaison->variation_pct, 1, ',', ' ') }} %
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Graphique d'évolution chronologique du stock --}}
                <div class="col-lg-7">
                    <div style="height: 210px;">
                        <canvas id="graph-evolution-stock"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
@elseif($filtres['stock_id'])
    <div class="alert alert-light border shadow-sm small mb-4">
        <i class="bi bi-info-circle text-primary me-1"></i> Ce stock ne dispose que d'un seul levé validé : au moins deux levés validés sont nécessaires pour établir une comparaison.
    </div>
@endif

{{-- 3. Tableau chronologique de l'historique --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-4 pb-2 d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i> Journal des levés topographiques validés</h2>
        <span class="text-muted small">{{ $leves->total() }} relevé{{ $leves->total() > 1 ? 's' : '' }}</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small">
                <tr>
                    <th class="ps-4">Date du levé</th>
                    <th>Stock & Matériau</th>
                    <th>Campagne</th>
                    <th class="text-end">Volume (m³)</th>
                    <th class="text-end">Variation </th>
                    <th>Technicien</th>
                    <th class="pe-4 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leves as $l)
                    <tr>
                        <td class="ps-4 fw-semibold text-nowrap">{{ $l->date_leve->format('d/m/Y') }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block shadow-sm flex-shrink-0" style="width: 10px; height: 10px; background: {{ $l->couleur }};"></span>
                                <div>
                                    <a href="{{ route('historique.index', ['stock_id' => $l->stock_id]) }}" class="fw-semibold text-reset text-decoration-none" title="Comparer l'historique de ce stock">
                                        {{ $l->stock->nom }}
                                    </a>
                                    <div class="small text-muted">{{ $l->stock->typeMateriau->libelle }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $l->campagne->libelle }}</td>
                        <td class="text-end fw-bold">{{ number_format($l->volume, 1, ',', ' ') }} m³</td>
                        <td class="text-end text-nowrap">
                            @if($l->delta === null)
                                <span class="badge bg-light text-muted border fw-normal">Relevé initial</span>
                            @elseif($l->delta > 0)
                                <span class="text-success fw-semibold small">
                                    <i class="bi bi-arrow-up-short"></i>+{{ number_format($l->delta, 1, ',', ' ') }} m³
                                    @if($l->variation_pct !== null)
                                        <span class="text-muted fw-normal">({{ number_format($l->variation_pct, 1, ',', ' ') }}%)</span>
                                    @endif
                                </span>
                            @elseif($l->delta < 0)
                                <span class="text-danger fw-semibold small">
                                    <i class="bi bi-arrow-down-short"></i>{{ number_format($l->delta, 1, ',', ' ') }} m³
                                    @if($l->variation_pct !== null)
                                        <span class="text-muted fw-normal">({{ number_format($l->variation_pct, 1, ',', ' ') }}%)</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-muted small">0,0 m³ (Stable)</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $l->technicien->matricule ?? '—' }}</td>
                        <td class="pe-4 text-end">
                            @unless($estConsultant)
                                <a href="{{ route('leves.show', $l) }}" class="btn btn-sm btn-outline-secondary">Détails</a>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Aucun levé validé ne correspond aux critères sélectionnés.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($leves->hasPages())
        <div class="card-footer bg-white py-3 px-4 border-top">
            {{ $leves->links() }}
        </div>
    @endif
</div>
@endsection

@if($comparaison)
    @php
        $serieData = $comparaison->leves_dispo->reverse()->values()->map(fn ($item) => [
            'date' => $item->date_leve->format('d/m/Y'),
            'volume' => round((float) $item->volume, 1),
        ]);
    @endphp

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var canvas = document.getElementById('graph-evolution-stock');
            if (!canvas || !window.Chart) { return; }

            var serie = @json($serieData);
            var couleurMateriau = @json($comparaison->couleur);

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: serie.map(function (p) { return p.date; }),
                    datasets: [{
                        label: 'Volume (m³)',
                        data: serie.map(function (p) { return p.volume; }),
                        borderColor: couleurMateriau,
                        backgroundColor: couleurMateriau + '22',
                        borderWidth: 2.5,
                        pointRadius: 4,
                        pointBackgroundColor: couleurMateriau,
                        fill: true,
                        tension: 0.25
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (c) {
                                    return Number(c.parsed.y).toLocaleString('fr-FR', { maximumFractionDigits: 1 }) + ' m³';
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (v) { return Number(v).toLocaleString('fr-FR'); }
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
@endif