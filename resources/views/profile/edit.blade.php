@extends('template')
@section('title', 'Mon profil')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted small mb-1">Compte</p>
        <h1 class="h4 mb-0">Mon profil</h1>
    </div>
    @if ($user->doit_changer_mdp)
    <div class="alert alert-warning shadow-sm small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Votre mot de passe a été réinitialisé par l'Administrateur. Saisissez le mot de passe temporaire reçu dans « Mot de passe actuel », puis choisissez le vôtre.
    </div>
    @endif
</div>

<form method="POST" action="{{ route('profile.update') }}" id="form-profil">
    @csrf
    @method('PATCH')

    <div class="row g-4">
        {{-- Informations personnelles (lecture seule, gérées par l'Administrateur) --}}
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3 fw-bold"><i class="bi bi-person-badge text-primary me-2"></i> Informations personnelles</h2>

                    <div class="mb-3">
                        <label for="matricule" class="form-label small fw-semibold">Matricule</label>
                        <input type="text" id="matricule" class="form-control bg-light" value="{{ $user->matricule }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="nom" class="form-label small fw-semibold">Nom</label>
                        <input type="text" id="nom" class="form-control bg-light" value="{{ $user->nom }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="prenom" class="form-label small fw-semibold">Prénom</label>
                        <input type="text" id="prenom" class="form-control bg-light" value="{{ $user->prenom }}" disabled>
                        <div class="form-text small text-muted">Ces informations sont gérées par l'Administrateur.</div>
                    </div>

                    <div class="text-muted small mt-4">
                        Rôle : <span class="badge text-bg-secondary">{{ $user->role->libelle() }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sécurité : modification du mot de passe --}}
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3 fw-bold"><i class="bi bi-shield-lock text-success me-2"></i> Sécurité · Modifier le mot de passe</h2>
                    <p class="text-muted small mb-3">Saisissez votre mot de passe actuel pour valider le changement. Le nouveau doit contenir au moins 8 caractères.</p>

                    <div class="mb-3">
                        <label for="current_password" class="form-label small fw-semibold">Mot de passe actuel *</label>
                        <div class="input-group">
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   autocomplete="current-password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password" title="Afficher / masquer">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">Nouveau mot de passe *</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   autocomplete="new-password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password" title="Afficher / masquer">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label small fw-semibold">Confirmer le nouveau mot de passe *</label>
                        <div class="input-group">
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control" autocomplete="new-password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirmation" title="Afficher / masquer">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div id="password-match-error" class="text-danger small mt-1 d-none">Les mots de passe ne correspondent pas.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 card border-0 shadow-sm bg-light">
        <div class="card-body p-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small"><i class="bi bi-info-circle me-1"></i> Seul le mot de passe sera mis à jour.</span>
            <button type="submit" class="btn btn-success px-4 fw-semibold">
                <i class="bi bi-check2-all me-2"></i> Mettre à jour le mot de passe
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Afficher / masquer les mots de passe
        document.querySelectorAll('.toggle-password').forEach(function (bouton) {
            bouton.addEventListener('click', function () {
                var input = document.getElementById(this.getAttribute('data-target'));
                var icone = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icone.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = 'password';
                    icone.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        });

        // 2. Vérification de la confirmation avant envoi
        var form = document.getElementById('form-profil');
        var pass = document.getElementById('password');
        var confirmation = document.getElementById('password_confirmation');
        var erreur = document.getElementById('password-match-error');

        if (form && pass && confirmation) {
            form.addEventListener('submit', function (e) {
                if (pass.value !== confirmation.value) {
                    e.preventDefault();
                    erreur.classList.remove('d-none');
                    confirmation.classList.add('is-invalid');
                    confirmation.focus();
                } else {
                    erreur.classList.add('d-none');
                    confirmation.classList.remove('is-invalid');
                }
            });
        }
    });
</script>
@endpush