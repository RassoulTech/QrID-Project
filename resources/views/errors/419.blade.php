@extends('errors.layout')

@section('code', 'Erreur 419')
@section('title', 'Page expirée')
@section('message', 'Cette page est restée ouverte trop longtemps. Par sécurité, il faut la recharger avant de continuer.')

@section('action')
    <div class="d-grid gap-2">
        <a href="{{ url()->previous() }}" class="btn btn-primary">Réessayer</a>
        <a href="{{ route('login') }}" class="btn btn-outline-secondary">Aller à la connexion</a>
    </div>
@endsection
