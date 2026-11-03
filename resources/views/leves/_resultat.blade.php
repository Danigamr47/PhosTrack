@php
    $precedent = $precedent ?? null;
    $delta = ($leve->volume !== null && $precedent && $precedent->volume > 0)
        ? ($leve->volume - $precedent->volume) / $precedent->volume * 100
        : null;
@endphp

<div class="card mt-4"><div class="card-body p-4">
    <h2 class="h6 mb-3">Résultat du calcul</h2>

    @if ($leve->volume === null)
        <p class="text-muted small mb-0">Pas encore calculé.</p>
    @else
        <div class="rounded p-3 mb-3" style="background: var(--phos-primary-100)">
            <div class="small text-muted">Volume du tas</div>
            <div class="fs-2 fw-bold" style="color: var(--phos-primary-700)">
                {{ number_format($leve->volume, 1, ',', ' ') }} m³
            </div>
        </div>

        <dl class="row small mb-0 gy-2">
            <dt class="col-6 fw-normal text-muted">Surface au sol</dt>
            <dd class="col-6 text-end fw-semibold mb-0">{{ number_format($leve->surface_sol, 1, ',', ' ') }} m²</dd>

            <dt class="col-6 fw-normal text-muted">Hauteur maximale</dt>
            <dd class="col-6 text-end fw-semibold mb-0">{{ number_format($leve->hauteur_max, 2, ',', ' ') }} m</dd>

            <dt class="col-6 fw-normal text-muted">Levé précédent</dt>
            <dd class="col-6 text-end fw-semibold mb-0">
                @if ($precedent)
                    {{ number_format($precedent->volume, 1, ',', ' ') }} m³
                    @if ($delta !== null)
                        <span class="badge text-bg-{{ $delta >= 0 ? 'success' : 'warning' }}">
                            {{ $delta >= 0 ? '+' : '−' }}{{ number_format(abs($delta), 1, ',', ' ') }} %
                        </span>
                    @endif
                @else
                    —
                @endif
            </dd>

            <dt class="col-6 fw-normal text-muted">Points utilisés</dt>
            <dd class="col-6 text-end fw-semibold mb-0">
                PIED {{ $stats->get('pied')->n ?? 0 }} · SEMIS {{ $stats->get('semis')->n ?? 0 }}
            </dd>
        </dl>
    @endif
</div></div>