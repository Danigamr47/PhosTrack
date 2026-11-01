@extends('template')
@section('title', "Journal d'activité")

@section('content')
<h1 class="h4 mb-3">Journal d'activité</h1>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
            <tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Entité</th></tr>
        </thead>
        <tbody>
        @forelse ($entrees as $e)
            <tr>
                <td>{{ $e->date_heure->format('d/m/Y H:i:s') }}</td>
                <td>{{ $e->user->matricule }} — {{ $e->user->nom_complet }}</td>
                <td>{{ $e->action }}</td>
                <td>{{ $e->entite }} #{{ $e->id_entite }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted">Aucune activité.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $entrees->links('pagination::bootstrap-5') }}</div>
@endsection