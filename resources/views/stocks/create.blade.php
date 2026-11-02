@extends('template')
@section('title', 'Nouveau stock')

@section('content')
<h1 class="h4 mb-3">Nouveau stock</h1>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('stocks.store') }}">
        @include('stocks._form')
    </form>
</div></div>
@endsection