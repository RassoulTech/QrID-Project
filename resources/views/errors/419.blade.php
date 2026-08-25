@extends('errors.layout')

@section('code', __('errors.419.code'))
@section('title', __('errors.419.titre'))
@section('message', __('errors.419.message'))

@section('action')
    <div class="d-grid gap-2">
        <a href="{{ url()->previous() }}" class="btn btn-primary">{{ __('common.actions.reessayer') }}</a>
        <a href="{{ route('login') }}" class="btn btn-outline-secondary">{{ __('errors.aller_connexion') }}</a>
    </div>
@endsection
