@extends('layouts.app')

@section('title', 'My Business') @section('heading', 'My Business') @section('subtitle',
'Manage
sender addresses and print return-address labels.')

@section('content')
    <nav class="crm-tabs">
        <a class="{{ $activeTab === 'address' ? 'active' : '' }}"
            href="{{ route('crm.my-business.index', ['tab' => 'address']) }}"
        >Addresses</a>
        <a class="{{ $activeTab === 'labels' ? 'active' : '' }}"
            href="{{ route('crm.my-business.index', ['tab' => 'labels']) }}"
        >Labels</a>
    </nav>
    @if ($activeTab === 'labels')
        <section class="crm-card">
            <h2>Print Avery 94200 labels</h2>
            <form method="GET" action="{{ route('crm.my-business.labels.print') }}">
                <div class="crm-field">
                    <label>Business</label>
                    <select class="crm-select" name="business_id">
                        @foreach ($businessProfiles as $profile)
                            <option value="{{ $profile['id'] }}" @selected(data_get($selectedLabelBusiness, 'id') === $profile['id'])>
                                {{ $profile['business_name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="crm-button crm-button-primary">Print Labels</button>
            </form>
        </section>
    @else
        <div class="crm-grid">
            <aside class="crm-col-4 crm-card">
                <h2>Businesses</h2>
                @foreach ($businessProfiles as $profile)
                    <p>
                        <a
                            href="{{ route('crm.my-business.index', ['business_id' => $profile['id']]) }}">{{ $profile['business_name'] }}</a>
                        @if ($profile['is_default'])
                            <span class="crm-badge">Default</span>
                        @endif
                    </p>
                @endforeach
                <a class="crm-button" href="{{ route('crm.my-business.index', ['mode' => 'create']) }}">
                    Add Business</a>
            </aside>
            <section class="crm-col-8 crm-card">
                <h2>{{ $mode === 'create' ? 'Add Business' : 'Edit Business' }}</h2>
                @php($profile = $mode === 'create' ? [] : $selectedBusiness ?? [])<form method="POST"
                    action="{{ $mode === 'create' ? route('crm.my-business.address.store') : route('crm.my-business.address.update', $profile['id']) }}">
                    @csrf @if ($mode !== 'create')
                        @method('PATCH')
                    @endif
                    <div class="crm-grid">
                        @foreach (['business_name' => 'Business name', 'street_line_1' => 'Street line 1', 'street_line_2' => 'Street line 2', 'city' => 'City', 'state' => 'State', 'postal_code' => 'Postal code', 'country' => 'Country'] as $key => $label)
                            <div class="crm-col-6 crm-field">
                                <label>{{ $label }}</label>
                                <input
                                    class="crm-input"
                                    name="{{ $key }}"
                                    value="{{ old($key, data_get($profile, $key)) }}"
                                    @required($key === 'business_name')>
                            </div>
                        @endforeach
                        <div class="crm-col-12 crm-field">
                            <label>
                                <input
                                    type="checkbox"
                                    name="is_default"
                                    value="1"
                                    @checked(old('is_default', data_get($profile, 'is_default')))
                                > Default business</label>
                        </div>
                    </div>
                    <div class="crm-actions">
                        <button class="crm-button crm-button-primary">Save
                            Business</button>
                    </div>
                </form>
                @if ($mode !== 'create')
                    <form method="POST" action="{{ route('crm.my-business.destroy', $profile['id']) }}">
                        @csrf @method('DELETE')<button class="crm-button crm-button-danger"
                            data-confirm="Delete this business?"
                        >Delete</button>
                    </form>
                @endif
            </section>
        </div>
    @endif
@endsection
