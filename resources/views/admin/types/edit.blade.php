@extends('template')
@section('title', 'Modifier le type')

@section('content')
<div class="mb-4">
    <p class="text-muted small mb-1">Administration › Types de matériau</p>
    <h1 class="h4 mb-0 fw-bold">Modifier le type de matériau</h1>
</div>

<div class="card border-0 shadow-sm" style="max-width: 500px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.types-materiau.update', $type) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label small fw-semibold" for="libelle">Libellé *</label>
                <input id="libelle" name="libelle" value="{{ old('libelle', $type->libelle) }}"
                       class="form-control form-control-sm @error('libelle') is-invalid @enderror" required>
                @error('libelle') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.types-materiau.index') }}" class="btn btn-outline-secondary btn-sm">Annuler</a>
                <button class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
@endsection