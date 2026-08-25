@extends('errors.layout')

@section('code', __('errors.429.code'))
@section('title', __('errors.429.titre'))
@section('message', __('errors.429.message'))

@section('action')
    <a href="{{ url('/') }}" class="btn btn-outline-secondary">{{ __('errors.retour_accueil') }}</a>
@endsection
