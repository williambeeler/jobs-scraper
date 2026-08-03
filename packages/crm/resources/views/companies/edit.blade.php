@extends('crm::layouts.app')

@section('title', 'Edit ' . $company['name']) @section('heading', 'Edit ' . $company['name'])

@section('subtitle', 'Update account details and append new context.')

@section('actions')
    <a class="crm-button" href="{{ route('crm.companies.show', $company['id']) }}">View
        Company</a>
    <a class="crm-button" href="{{ route('crm.companies.index') }}">Companies</a>
@endsection

@section('content')
    <form
        class="crm-card"
        method="POST"
        action="{{ route('crm.companies.update', $company['id']) }}">
        @csrf @method('PUT') @include('crm::companies._form', ['submitLabel' => 'Save Changes'])</form>
@endsection
