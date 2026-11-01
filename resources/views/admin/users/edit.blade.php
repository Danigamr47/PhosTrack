@extends('template')
@section('title', 'Modifier le compte')

@section('content')
<h1 class="h4 mb-3">Modifier : {{ $user->nom_complet }}</h1>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.utilisateurs.update', $user) }}">
        @method('PUT')
        @include('admin.users._form')
    </form>
</div></div>
@endsection