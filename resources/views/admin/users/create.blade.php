@extends('template')
@section('title', 'Nouveau compte')

@section('content')
<h1 class="h4 mb-3">Nouveau compte</h1>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.utilisateurs.store') }}">
        @include('admin.users._form')
    </form>
</div></div>
@endsection