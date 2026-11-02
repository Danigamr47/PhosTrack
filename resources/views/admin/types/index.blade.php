@extends('template')
@section('title', 'Types de matériau')

@section('content')
<h1 class="h4 mb-3">Types de matériau</h1>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h6">Ajouter un type</h2>
            <form method="POST" action="{{ route('admin.types-materiau.store') }}">
                @csrf
                <input name="libelle" value="{{ old('libelle') }}" placeholder="Ex. : Sable"
                       class="form-control @error('libelle') is-invalid @enderror" required>
                @error('libelle') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <button class="btn btn-primary w-100 mt-3">Ajouter</button>
            </form>
        </div></div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm"><div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Libellé</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @foreach ($types as $t)
                    <tr>
                        <td>{{ $t->libelle }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.types-materiau.edit', $t) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                            <form method="POST" action="{{ route('admin.types-materiau.destroy', $t) }}" class="d-inline"
                                  onsubmit="return confirm('Supprimer ce type ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div></div>
    </div>
</div>
@endsection