@extends('template')

@section('title', 'Connexion')

@section('content')
<div class="row justify-content-center">
    <div class="col-11 col-sm-8 col-md-5 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-4">{{ config('app.name') }}</h1>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="matricule" class="form-label">Matricule</label>
                        <input id="matricule" type="text" name="matricule"
                               value="{{ old('matricule') }}"
                               class="form-control @error('matricule') is-invalid @enderror"
                               required autofocus>
                        @error('matricule')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input id="password" type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection