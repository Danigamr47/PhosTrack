@extends('template')

@section('title', 'Tableau de bord')

@section('content')
    <h1 class="h4">Bonjour {{ auth()->user()->nom_complet }}</h1>
    <p>Rôle : <strong>{{ auth()->user()->role->libelle() }}</strong></p>
@endsection