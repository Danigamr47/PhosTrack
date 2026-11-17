@csrf
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label small fw-semibold" for="matricule">Matricule *</label>
        <input id="matricule" name="matricule" type="text"
               value="{{ old('matricule', $user->matricule ?? '') }}"
               class="form-control form-control-sm @error('matricule') is-invalid @enderror"
               @isset($user) disabled title="Le matricule ne peut pas être modifié" @else required @endisset>
        @isset($user)
            <input type="hidden" name="matricule" value="{{ $user->matricule }}">
        @endisset
        @error('matricule') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    
    <div class="col-md-4">
        <label class="form-label small fw-semibold" for="nom">Nom *</label>
        <input id="nom" name="nom" type="text" 
               value="{{ old('nom', $user->nom ?? '') }}"
               class="form-control form-control-sm @error('nom') is-invalid @enderror"
               @isset($user) disabled title="L'identité est gérée par l'administration" @else required @endisset>
        @isset($user)
            <input type="hidden" name="nom" value="{{ $user->nom }}">
        @endisset
        @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    
    <div class="col-md-4">
        <label class="form-label small fw-semibold" for="prenom">Prénom *</label>
        <input id="prenom" name="prenom" type="text" 
               value="{{ old('prenom', $user->prenom ?? '') }}"
               class="form-control form-control-sm @error('prenom') is-invalid @enderror"
               @isset($user) disabled title="L'identité est gérée par l'administration" @else required @endisset>
        @isset($user)
            <input type="hidden" name="prenom" value="{{ $user->prenom }}">
        @endisset
        @error('prenom') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    
    <div class="col-md-4">
        <label class="form-label small fw-semibold" for="role">Rôle *</label>
        <select id="role" name="role" class="form-select form-select-sm @error('role') is-invalid @enderror" required>
            @foreach (\App\Enums\Role::cases() as $r)
                <option value="{{ $r->value }}"
                    @selected(old('role', isset($user) ? $user->role->value : 'consultant') === $r->value)>
                    {{ $r->libelle() }}
                </option>
            @endforeach
        </select>
        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Section Sécurité & Mot de passe --}}
    <div class="col-12"><hr class="my-2"></div>
    <div class="col-12">
        <h2 class="h6 fw-bold text-dark mb-1"><i class="bi bi-shield-lock text-warning me-1"></i> Sécurité & Accès</h2>
        <p class="text-muted small">
            @isset($user)
                Réinitialisation forcée du mot de passe en cas d'oubli par l'employé. Laissez ces champs vides si vous ne souhaitez pas le modifier.
            @else
                Définissez le mot de passe initial pour ce nouveau compte.
            @endisset
        </p>
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold" for="password">
            Nouveau mot de passe @isset($user) <span class="text-muted fw-normal">(laisser vide pour conserver l'actuel)</span> @endisset
        </label>
        <input id="password" name="password" type="password"
               class="form-control form-control-sm @error('password') is-invalid @enderror"
               @empty($user) required @endempty>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    
    <div class="col-md-6">
        <label class="form-label small fw-semibold" for="password_confirmation">Confirmation du mot de passe</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control form-control-sm">
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-outline-secondary btn-sm">Annuler</a>
    <button class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">Enregistrer les modifications</button>
</div>