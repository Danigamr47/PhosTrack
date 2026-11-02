@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="nom">Nom du stock</label>
        <input id="nom" name="nom" value="{{ old('nom', $stock->nom ?? '') }}"
               placeholder="Ex. : Tas 2 — Charbon"
               class="form-control @error('nom') is-invalid @enderror" required>
        @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="type_materiau_id">Type de matériau</label>
        <select id="type_materiau_id" name="type_materiau_id"
                class="form-select @error('type_materiau_id') is-invalid @enderror" required>
            <option value="">— Choisir —</option>
            @foreach ($types as $t)
                <option value="{{ $t->id }}"
                    @selected((int) old('type_materiau_id', $stock->type_materiau_id ?? 0) === $t->id)>
                    {{ $t->libelle }}
                </option>
            @endforeach
        </select>
        @error('type_materiau_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-primary">Enregistrer</button>
    <a href="{{ route('stocks.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>