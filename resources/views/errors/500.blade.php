@extends('errors.layout')

@section('code', 'Erreur 500')
@section('title', 'Une erreur est survenue')
@section('message', 'Le problème vient de nous, pas de vous. Notre équipe en a été informée. Réessayez dans un instant.')

@section('action')
    <div class="d-grid gap-2">
        <a href="{{ url()->previous() }}" class="btn btn-primary">Réessayer</a>
        <a href="{{ url('/') }}" class="btn btn-outline-secondary">Retour à l'accueil</a>
    </div>
@endsection
