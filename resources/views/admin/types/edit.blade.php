@extends('template')
@section('title', 'Modifier le type')

@section('content')
<h1 class="h4 mb-3">Modifier le type de matériau</h1>
<div class="card shadow-sm" style="max-width: 480px"><div class="card-body">
    <form method="POST" action="{{ route('admin.types-materiau.update', $type) }}">
        @csrf @method('PUT')
        <label class="form-label" for="libelle">Libellé</label>
        <input id="libelle" name="libelle" value="{{ old('libelle', $type->libelle) }}"
               class="form-control @error('libelle') is-invalid @enderror" required>
        @error('libelle') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="mt-3">
            <button class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.types-materiau.index') }}" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>
</div></div>
@endsection