@extends('layouts.app')

@section('title', 'Add Company | ' . config('app.name'))

@section('content')
    <main class="container py-5" style="max-width: 960px;">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">CRM company</p>
            <h1 class="display-6 fw-bold mb-2">Add company</h1>
            <p class="text-secondary mb-0">Create a new account and add its initial context.</p>
        </header>

        <form method="POST" action="{{ route('crm.companies.store') }}">
            @csrf
            @include('vendor.crm.companies._form-app', ['submitLabel' => 'Save company'])
        </form>
    </main>
@endsection
