@extends('errors.layout')

@section('code', __('errors.403.code'))
@section('title', __('errors.403.titre'))
@section('message', __('errors.403.message'))

@section('action')
    <div class="d-grid gap-2">
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary">{{ __('errors.retour_espace') }}</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary">{{ __('errors.se_connecter') }}</a>
        @endauth
    </div>
@endsection
