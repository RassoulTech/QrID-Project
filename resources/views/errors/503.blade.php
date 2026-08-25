@extends('errors.layout')

@section('code', __('errors.503.code'))
@section('title', __('errors.503.titre'))
@section('message', __('errors.503.message'))

@section('action')
    <a href="{{ url('/') }}" class="btn btn-outline-secondary">{{ __('common.actions.reessayer') }}</a>
@endsection
