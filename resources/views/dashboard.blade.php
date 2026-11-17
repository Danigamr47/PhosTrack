@extends('template')
@section('title', 'Tableau de bord')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="text-muted small mb-1">Tableau de bord opérationnel</p>
        <h1 class="h4 mb-0 fw-bold">Bonjour, {{ auth()->user()->nom_complet }}</h1>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        @if(!auth()->user()->aLeRole(\App\Enums\Role::Consultant))
            <a href="{{ route('leves.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Nouveau levé
            </a>
        @endif
        <span class="text-muted small bg-light px-3 py-1 rounded border">
            <i class="bi bi-calendar3 me-1"></i> Situation au {{ now()->format('d/m/Y') }}
        </span>
    </div>
</div>

{{-- Indicateurs (KPIs) modernisés avec icônes et alerte contextuelle --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm border-start border-primary border-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted fw-semibold text-uppercase">Volume total actuel</div>
                    <div class="fs-3 fw-bold text-dark mt-1" style="color: var(--phos-primary-700)">{{ number_format($stats['volume_total'], 0, ',', ' ') }} <span class="fs-6 text-muted">m³</span></div>
                    <div class="small text-muted" style="font-size: 0.75rem;">Dernier levé validé par stock</div>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-layers-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm border-start border-info border-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted fw-semibold text-uppercase">Stocks suivis</div>
                    <div class="fs-3 fw-bold text-dark mt-1">{{ $stats['nb_stocks'] }}</div>
                    <div class="small mt-1"><a href="{{ route('stocks.index') }}" class="text-decoration-none fw-semibold">Voir la liste <i class="bi bi-arrow-right small"></i></a></div>
                </div>
                <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm border-start border-success border-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted fw-semibold text-uppercase">Levés du mois</div>
                    <div class="fs-3 fw-bold text-dark mt-1">{{ $stats['leves_mois'] }}</div>
                    <div class="small mt-1"><a href="{{ route('leves.index') }}" class="text-decoration-none fw-semibold text-success">Voir les levés <i class="bi bi-arrow-right small"></i></a></div>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm border-start {{ $stats['draglines_proches'] > 0 ? 'border-warning bg-warning bg-opacity-10' : 'border-secondary' }} border-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted fw-semibold text-uppercase">Draglines en alerte</div>
                    <div class="fs-3 fw-bold {{ $stats['draglines_proches'] > 0 ? 'text-danger' : 'text-dark' }} mt-1">{{ $stats['draglines_proches'] }}</div>
                    <div class="small mt-1"><a href="{{ route('draglines.index') }}" class="text-decoration-none fw-semibold {{ $stats['draglines_proches'] > 0 ? 'text-dark' : '' }}">Voir le suivi <i class="bi bi-arrow-right small"></i></a></div>
                </div>
                <div class="{{ $stats['draglines_proches'] > 0 ? 'bg-warning text-dark' : 'bg-secondary bg-opacity-10 text-secondary' }} p-3 rounded-circle fs-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Graphiques --}}
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h6 mb-3 fw-bold"><i class="bi bi-pie-chart text-primary me-2"></i> Volume par matériau</h2>
                @if ($materiaux->isEmpty())
                    <p class="text-muted small mb-0">Aucun levé validé pour le moment.</p>
                @else
                    <div style="height: 200px"><canvas id="graph-materiaux"></canvas></div>
                    <ul class="list-unstyled small mt-3 mb-0">
                        @foreach ($materiaux as $m)
                            <li class="d-flex justify-content-between py-1 border-bottom border-light">
                                <span><span class="d-inline-block rounded-circle me-1 shadow-sm" style="width: 10px; height: 10px; background: {{ $m->couleur }}"></span> {{ $m->materiau }}
                                    <span class="text-muted">· {{ $m->nb_stocks }} stock{{ $m->nb_stocks > 1 ? 's' : '' }}</span></span>
                                <span class="fw-bold">{{ number_format($m->volume, 0, ',', ' ') }} m³</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h6 mb-1 fw-bold"><i class="bi bi-bar-chart-fill text-success me-2"></i> Évolution par campagne</h2>
                <p class="text-muted small mb-3">Volume cumulé des levés validés de chaque campagne.</p>
                @if (count($graphiques['campagnes']['labels']) === 0)
                    <p class="text-muted small mb-0">Aucune campagne avec un levé validé.</p>
                @else
                    <div style="height: 260px"><canvas id="graph-campagnes"></canvas></div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Draglines --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4 pb-2 d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0 fw-bold"><i class="bi bi-truck-flatbed text-secondary me-2"></i> Suivi des Draglines & Sécurité</h2>
        <a href="{{ route('draglines.index') }}" class="small text-decoration-none fw-semibold">Voir tout <i class="bi bi-chevron-right small"></i></a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small">
                <tr>
                    <th class="ps-4">Dragline</th>
                    <th>Dernière position</th>
                    <th>Passe</th>
                    <th class="text-end">Distance à la limite</th>
                    <th class="pe-4 text-end">Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($draglines as $d)
                    @php($l = $d->loc)
                    <tr>
                        <td class="ps-4"><a href="{{ route('draglines.index', ['dragline' => $d->id]) }}" class="fw-semibold text-reset text-decoration-none">{{ $d->nom }}</a></td>
                        <td>{{ $d->date ? $d->date->format('d/m/Y') : '—' }}</td>
                        <td>
                            @if ($l && in_array($l->statut, ['normal', 'proche']))
                                Passe {{ $l->passe }} <span class="small text-muted">· PF{{ $l->pf_de }} – PF{{ $l->pf_a }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end fw-semibold">{{ $l && in_array($l->statut, ['normal', 'proche']) ? number_format($l->limite, 1, ',', ' ').' m' : '—' }}</td>
                        <td class="pe-4 text-end">
                            @if (! $d->date)
                                <span class="text-muted small">Aucune position</span>
                            @elseif (! $l || $l->statut === 'inconnu')
                                —
                            @elseif ($l->statut === 'proche')
                                <span class="badge text-bg-warning px-2 py-1"><i class="bi bi-exclamation-triangle me-1"></i>Proche de la limite</span>
                            @elseif ($l->statut === 'hors_plan')
                                <span class="badge text-bg-secondary px-2 py-1">Hors des passes</span>
                            @else
                                <span class="badge text-bg-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Normal</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucune dragline enregistrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Levés en cours et Activité récente --}}
@if (! $estConsultant)
    <div class="row g-4 mb-4">
        <div class="{{ $estAdmin ? 'col-lg-6' : 'col-12' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4 pb-2"><h2 class="h6 mb-0 fw-bold"><i class="bi bi-hourglass-split text-warning me-2"></i> Levés en cours de validation</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            @forelse ($enCours as $l)
                                <tr>
                                    <td class="ps-4">
                                        <a href="{{ route('leves.show', $l) }}" class="fw-semibold text-reset text-decoration-none">{{ $l->stock->nom }}</a>
                                        <div class="small text-muted">{{ $l->date_leve->format('d/m/Y') }} · Matricule : {{ $l->technicien->matricule }}</div>
                                    </td>
                                    <td class="pe-4 text-end"><span class="badge text-bg-{{ $l->statut->couleur() }} px-2 py-1">{{ $l->statut->libelle() }}</span></td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-4">Aucun levé en cours actuellement.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($estAdmin)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 pb-2 d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0 fw-bold"><i class="bi bi-clock-history text-info me-2"></i> Activité récente du système</h2>
                        <a href="{{ route('admin.journal.index') }}" class="small text-decoration-none fw-semibold">Journal complet <i class="bi bi-chevron-right small"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                                @forelse ($activite as $a)
                                    <tr>
                                        <td class="text-muted small text-nowrap ps-4">{{ $a->date_heure->format('d/m H:i') }}</td>
                                        <td class="fw-semibold small">{{ $a->user->matricule ?? 'Système' }}</td>
                                        <td class="small pe-4">{{ $a->action }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center text-muted py-4" colspan="3">Aucune activité récente.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
    window.PHOSTRACK_DASH = @json($graphiques);
</script>
<script src="{{ asset('js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}"></script>
@endpush