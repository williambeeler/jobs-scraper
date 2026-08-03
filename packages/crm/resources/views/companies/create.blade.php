@extends('crm::layouts.app')

@section('title', 'Create Company') @section('heading', 'Create Company') @section('subtitle',
'Add
a new account and its initial context.')

@section('actions')
    <a class="crm-button" href="{{ route('crm.companies.index') }}">Back to
        Companies</a>
@endsection

@section('content')
    <form
        class="crm-card"
        method="POST"
        action="{{ route('crm.companies.store') }}">
        @csrf @include('crm::companies._form', ['submitLabel' => 'Save Company'])</form>
@endsection
