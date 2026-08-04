@extends('crm::layouts.app')

@section('title', 'Companies')

@section('heading', 'Companies')

@section('subtitle')
    {{ trans_choice(':count company|:count companies', $companies->total(), ['count' => number_format($companies->total())]) }}
@endsection

@section('actions')
    <a class="crm-button crm-button-primary" href="{{ route('crm.companies.create') }}">Add Company</a>
@endsection

@section('content')
    @include('crm::companies._index-content')
@endsection
