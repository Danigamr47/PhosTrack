@extends('template')
@section('title', 'Stocks')

@php($estTechnicien = auth()->user()->aLeRole(\App\Enums\Role::Technicien))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Stocks</h1>
    @if ($estTechnicien)
        <a href="{{ route('stocks.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nouveau stock
        </a>
    @endif
</div>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Nom</th><th>Type de matériau</th>
                @if ($estTechnicien) <th class="text-end">Actions</th> @endif
            </tr>
        </thead>
        <tbody>
        @forelse ($stocks as $s)
            <tr>
                <td>{{ $s->nom }}</td>
                <td><span class="badge text-bg-secondary">{{ $s->typeMateriau->libelle }}</span></td>
                @if ($estTechnicien)
                    <td class="text-end">
                        <a href="{{ route('stocks.edit', $s) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                        <form method="POST" action="{{ route('stocks.destroy', $s) }}" class="d-inline"
                              onsubmit="return confirm('Supprimer ce stock ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                        </form>
                    </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-muted">Aucun stock enregistré.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection