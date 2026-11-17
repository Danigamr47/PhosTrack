@extends('template')
@section('title', 'Nouveau compte')

@section('content')
<div class="mb-4">
    <p class="text-muted small mb-1">Administration › Utilisateurs</p>
    <h1 class="h4 mb-0 fw-bold">Créer un nouveau compte</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.utilisateurs.store') }}">
            @include('admin.users._form')
        </form>
    </div>
</div>
@endsection