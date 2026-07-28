@extends('crm::layouts.app')

@section('title', 'Saved Searches') @section('heading', 'Saved Searches') @section('subtitle',
'Return to frequently used company filters.')

@section('content')
    <section class="crm-card">
        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Filters</th>
                        <th>Saved</th>
                        <th>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($savedSearches['data'] as $search)
                        <tr>
                            <td>{{ $search['name'] }}</td>
                            <td>
                                @foreach ($search['parameters'] as $key => $value)
                                    <span class="crm-badge">{{ str($key)->headline() }}:
                                        {{ $value }}</span>
                                @endforeach
                            </td>
                            <td>{{ $search['created_at_label'] }}</td>
                            <td>
                                <a class="crm-button" href="{{ $search['open_url'] }}">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="crm-empty">No saved searches yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
