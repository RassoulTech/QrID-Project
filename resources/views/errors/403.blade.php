@extends('errors.layout')

@section('code', 'Erreur 403')
@section('title', 'Accès refusé')
@section('message', 'Vous n\'avez pas les droits nécessaires pour consulter cette page.')

@section('action')
    <div class="d-grid gap-2">
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Retour à mon espace</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary">Se connecter</a>
        @endauth
    </div>
@endsection
