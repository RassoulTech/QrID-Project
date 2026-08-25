@extends('errors.layout')

@section('code', __('errors.500.code'))
@section('title', __('errors.500.titre'))
@section('message', __('errors.500.message'))

@section('action')
    <div class="d-grid gap-2">
        <a href="{{ url()->previous() }}" class="btn btn-primary">{{ __('common.actions.reessayer') }}</a>
        <a href="{{ url('/') }}" class="btn btn-outline-secondary">{{ __('errors.retour_accueil') }}</a>
    </div>
@endsection
