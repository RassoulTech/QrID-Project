@extends('errors.layout')

@section('code', 'Maintenance')
@section('title', 'Service temporairement indisponible')
@section('message', 'Nous effectuons une mise à jour. Le service revient dans quelques minutes.')

@section('action')
    <a href="{{ url('/') }}" class="btn btn-outline-secondary">Réessayer</a>
@endsection
