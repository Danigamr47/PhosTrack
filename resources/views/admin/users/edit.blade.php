@extends('template')
@section('title', 'Modifier le compte')

@section('content')
<div class="mb-4">
    <p class="text-muted small mb-1">Administration › Utilisateurs</p>
    <h1 class="h4 mb-0 fw-bold">Modifier le compte de : <span class="text-primary">{{ $user->nom_complet }}</span></h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.utilisateurs.update', $user) }}">
            @method('PUT')
            @include('admin.users._form')
        </form>
    </div>
</div>
@endsection