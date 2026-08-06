@extends('layouts.app')

@section('title', 'My Business | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">CRM</p>
            <h1 class="display-6 fw-bold mb-2">My Business</h1>
            <p class="text-secondary mb-0">Manage sender addresses and print return-address labels.</p>
        </header>

        <nav class="nav nav-pills gap-2 mb-4" aria-label="My Business sections">
            <a class="nav-link {{ $activeTab === 'address' ? 'active' : '' }}"
                href="{{ route('crm.my-business.index', ['tab' => 'address']) }}"
            >Addresses</a>
            <a class="nav-link {{ $activeTab === 'labels' ? 'active' : '' }}"
                href="{{ route('crm.my-business.index', ['tab' => 'labels']) }}"
            >Labels</a>
        </nav>

        @if ($activeTab === 'labels')
            <section class="card shadow-sm">
                <div class="card-header bg-white p-4 pb-0 border-0">
                    <h2 class="h5 mb-1">Print Avery 94200 labels</h2>
                    <p class="small text-body-secondary mb-0">Choose the return address to print.</p>
                </div>
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('crm.my-business.labels.print') }}">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="business_id">Business</label>
                            <select
                                class="form-select"
                                id="business_id"
                                name="business_id"
                            >
                                @foreach ($businessProfiles as $profile)
                                    <option value="{{ $profile['id'] }}" @selected(data_get($selectedLabelBusiness, 'id') === $profile['id'])>
                                        {{ $profile['business_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">Print labels</button>
                    </form>
                </div>
            </section>
        @else
            <div class="row g-4">
                <aside class="col-12 col-lg-4">
                    <section class="card shadow-sm">
                        <div class="card-header bg-white p-4 pb-0 border-0">
                            <h2 class="h5 mb-3">Businesses</h2>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse ($businessProfiles as $businessProfile)
                                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2 px-4 py-3
                                    {{ data_get($selectedBusiness, 'id') === $businessProfile['id'] && $mode !== 'create' ? 'active' : '' }}"
                                    href="{{ route('crm.my-business.index', ['business_id' => $businessProfile['id']]) }}"
                                >
                                    <span>{{ $businessProfile['business_name'] }}</span>
                                    @if ($businessProfile['is_default'])
                                        <span
                                            class="badge rounded-pill {{ data_get($selectedBusiness, 'id') === $businessProfile['id'] ? 'text-bg-light' : 'text-bg-secondary' }}"
                                        >Default</span>
                                    @endif
                                </a>
                            @empty
                                <p class="text-body-secondary px-4">No businesses yet.</p>
                            @endforelse
                        </div>
                        <div class="card-body p-4">
                            <a class="btn btn-outline-primary w-100"
                                href="{{ route('crm.my-business.index', ['mode' => 'create']) }}"
                            >Add business</a>
                        </div>
                    </section>
                </aside>

                <div class="col-12 col-lg-8">
                    <section class="card shadow-sm">
                        <div class="card-header bg-white p-4 pb-0 border-0">
                            <h2 class="h5 mb-1">
                                {{ $mode === 'create' ? 'Add business' : 'Edit business' }}</h2>
                            <p class="small text-body-secondary mb-0">This address can be used for
                                return-address labels.</p>
                        </div>
                        <div class="card-body p-4">
                            @php($profile = $mode === 'create' ? [] : $selectedBusiness ?? [])
                            <form method="POST"
                                action="{{ $mode === 'create' ? route('crm.my-business.address.store') : route('crm.my-business.address.update', $profile['id']) }}"
                            >
                                @csrf
                                @if ($mode !== 'create')
                                    @method('PATCH')
                                @endif
                                <div class="row g-3">
                                    @foreach (['business_name' => 'Business name', 'street_line_1' => 'Street line 1', 'street_line_2' => 'Street line 2', 'city' => 'City', 'state' => 'State', 'postal_code' => 'Postal code', 'country' => 'Country'] as $key => $label)
                                        <div
                                            class="col-12 {{ in_array($key, ['city', 'state', 'postal_code']) ? 'col-md-4' : 'col-md-6' }}">
                                            <label class="form-label fw-semibold"
                                                for="{{ $key }}"
                                            >{{ $label }}</label>
                                            <input
                                                class="form-control @error($key) is-invalid @enderror"
                                                id="{{ $key }}"
                                                name="{{ $key }}"
                                                value="{{ old($key, data_get($profile, $key)) }}"
                                                @required($key === 'business_name')
                                            >
                                            @error($key)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                role="switch"
                                                id="is_default"
                                                name="is_default"
                                                value="1"
                                                @checked(old('is_default', data_get($profile, 'is_default')))
                                            >
                                            <label class="form-check-label fw-semibold"
                                                for="is_default">Default business</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
                                    @if ($mode !== 'create')
                                        <button
                                            class="btn btn-outline-danger"
                                            type="submit"
                                            form="delete-business-form"
                                        >Delete business</button>
                                    @else
                                        <span></span>
                                    @endif
                                    <button class="btn btn-primary" type="submit">Save
                                        business</button>
                                </div>
                            </form>
                            @if ($mode !== 'create')
                                <form
                                    id="delete-business-form"
                                    method="POST"
                                    action="{{ route('crm.my-business.destroy', $profile['id']) }}"
                                    onsubmit="return confirm('Delete this business?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        @endif
    </main>
@endsection
