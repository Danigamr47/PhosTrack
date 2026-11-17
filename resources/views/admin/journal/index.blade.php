@extends('template')
@section('title', "Journal d'activité")

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted small mb-1">Administration › Traçabilité</p>
        <h1 class="h4 mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Journal d'activité</h1>
    </div>
    <span class="badge text-bg-secondary px-3 py-2">
        <i class="bi bi-shield-check me-1"></i> Audit système
    </span>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small">
                <tr>
                    <th class="ps-4">Date & Heure</th>
                    <th>Utilisateur</th>
                    <th>Action effectuée</th>
                    <th class="pe-4">Entité concernée</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($entrees as $e)
                <tr>
                    {{-- Date et heure formatées proprement --}}
                    <td class="ps-4 text-muted small">
                        <i class="bi bi-calendar3 me-1 text-secondary"></i>
                        {{ $e->date_heure->format('d/m/Y') }} 
                        <span class="fw-semibold text-dark ms-1">{{ $e->date_heure->format('H:i:s') }}</span>
                    </td>

                    {{-- Utilisateur (Nom complet sans le matricule, avec icône) --}}
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="bg-light rounded-circle p-1 me-2 text-primary d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                <i class="bi bi-person fs-6"></i>
                            </div>
                            <span class="fw-semibold text-dark">{{ $e->user->nom_complet ?? 'Utilisateur inconnu' }}</span>
                        </div>
                    </td>

                    {{-- Action avec badge contextuel selon le type --}}
                    <td>
                        @php
                            $actionLower = strtolower($e->action);
                            $badgeClass = 'text-bg-secondary';
                            if (str_contains($actionLower, 'créé') || str_contains($actionLower, 'activé')) {
                                $badgeClass = 'text-bg-success';
                            } elseif (str_contains($actionLower, 'modifié') || str_contains($actionLower, 'mis à jour')) {
                                $badgeClass = 'text-bg-primary';
                            } elseif (str_contains($actionLower, 'supprimé') || str_contains($actionLower, 'désactivé')) {
                                $badgeClass = 'text-bg-danger';
                            } elseif (str_contains($actionLower, 'mot de passe')) {
                                $badgeClass = 'text-bg-warning';
                            }
                        @endphp
                        <span class="badge {{ $badgeClass }} fw-normal px-2 py-1">
                            {{ $e->action }}
                        </span>
                    </td>

                    {{-- Entité impactée --}}
                    <td class="pe-4 text-muted small font-monospace">
                        <span class="badge bg-light text-dark border">
                            {{ $e->entite }} <span class="text-primary">#{{ $e->id_entite }}</span>
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                        Aucune activité enregistrée pour le moment.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $entrees->links('pagination::bootstrap-5') }}
</div>
@endsection