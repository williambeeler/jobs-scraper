@extends('crm::layouts.app')

@section('title', 'CRM Tools')

@section('heading', 'Tools')

@section('subtitle', 'Manage CRM statuses and platform connections.')

@section('content')
    @include('crm::partials.tools-tabs')
@endsection
