@csrf
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="matricule">Matricule</label>
        <input id="matricule" name="matricule" type="text"
               value="{{ old('matricule', $user->matricule ?? '') }}"
               class="form-control @error('matricule') is-invalid @enderror" required>
        @error('matricule') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="nom">Nom</label>
        <input id="nom" name="nom" type="text" value="{{ old('nom', $user->nom ?? '') }}"
               class="form-control @error('nom') is-invalid @enderror" required>
        @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="prenom">Prénom</label>
        <input id="prenom" name="prenom" type="text" value="{{ old('prenom', $user->prenom ?? '') }}"
               class="form-control @error('prenom') is-invalid @enderror" required>
        @error('prenom') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="role">Rôle</label>
        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
            @foreach (\App\Enums\Role::cases() as $r)
                <option value="{{ $r->value }}"
                    @selected(old('role', isset($user) ? $user->role->value : 'consultant') === $r->value)>
                    {{ $r->libelle() }}
                </option>
            @endforeach
        </select>
        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="password">
            Mot de passe @isset($user) <small class="text-muted">(laisser vide pour ne pas changer)</small> @endisset
        </label>
        <input id="password" name="password" type="password"
               class="form-control @error('password') is-invalid @enderror"
               @empty($user) required @endempty>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="password_confirmation">Confirmation</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control">
    </div>
</div>

<div class="mt-4">
    <button class="btn btn-primary">Enregistrer</button>
    <a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>