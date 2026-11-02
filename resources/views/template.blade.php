<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Accueil') | {{ config('app.name') }}</title>

    {{-- Bootstrap 5 + icônes (CDN) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Styles PhosTrack (couleurs de la charte) --}}
    <link href="{{ asset('css/phostrack.css') }}?v={{ filemtime(public_path('css/phostrack.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-light">

    @auth
        @include('partials.navbar')
    @endauth

    <main class="container-fluid py-4">
    @if (session('succes'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('succes') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('erreur'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('erreur') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>