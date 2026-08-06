@extends('layouts.app')

@section('title', 'Edit ' . $company['name'] . ' | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        <header
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"
        >
            <div>
                <p class="mb-2 text-uppercase text-primary fw-semibold small">CRM company</p>
                <h1 class="display-6 fw-bold mb-2">Edit {{ $company['name'] }}</h1>
                <p class="text-secondary mb-0">Update account details and add new context.</p>
            </div>
            <a class="btn btn-outline-secondary flex-shrink-0"
                href="{{ route('crm.companies.show', $company['id']) }}"
            >View company</a>
        </header>

        <form method="POST" action="{{ route('crm.companies.update', $company['id']) }}">
            @csrf
            @method('PUT')
            @include('crm.companies._form-app', ['submitLabel' => 'Save changes'])
        </form>
    </main>
@endsection
