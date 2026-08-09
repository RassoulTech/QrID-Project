@extends('errors.layout')

@section('code', 'Erreur 429')
@section('title', 'Trop de tentatives')
@section('message', 'Vous avez effectué trop de tentatives en peu de temps. Patientez une minute avant de réessayer.')

@section('action')
    <a href="{{ url('/') }}" class="btn btn-outline-secondary">Retour à l'accueil</a>
@endsection
