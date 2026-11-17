@extends('template')
@section('title', 'Types de matériau')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted small mb-1">Administration › Paramétrage</p>
        <h1 class="h4 mb-0 fw-bold">Types de matériau</h1>
    </div>
</div>

<div class="row g-4">
    {{-- Formulaire d'ajout --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h6 mb-3 fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i> Ajouter un type</h2>
                <form method="POST" action="{{ route('admin.types-materiau.store') }}">
                    @csrf
                    <label class="form-label small fw-semibold" for="libelle">Libellé *</label>
                    <input id="libelle" name="libelle" value="{{ old('libelle') }}" placeholder="Ex. : Sable"
                           class="form-control form-control-sm @error('libelle') is-invalid @enderror" required>
                    @error('libelle') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <button class="btn btn-primary btn-sm w-100 mt-3 fw-semibold shadow-sm">Ajouter</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Liste des types --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th class="ps-4">Libellé</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($types as $t)
                        <tr>
                            <td class="ps-4 fw-semibold text-dark">{{ $t->libelle }}</td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.types-materiau.edit', $t) }}" class="btn btn-sm btn-outline-primary border-0" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.types-materiau.destroy', $t) }}" class="d-inline"
                                      onsubmit="return confirm('Supprimer ce type ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger border-0" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">Aucun type de matériau enregistré.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection