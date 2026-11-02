@extends('template')
@section('title', 'Modifier le stock')

@section('content')
<h1 class="h4 mb-3">Modifier : {{ $stock->nom }}</h1>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('stocks.update', $stock) }}">
        @method('PUT')
        @include('stocks._form')
    </form>
</div></div>
@endsection