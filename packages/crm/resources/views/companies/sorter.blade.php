@extends('crm::layouts.printer')

@section('content')

    <h1>Tagged with {{ $activeTag }}</h1>

    @foreach($companies as $company)
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title mb-2">{{ $company->name }}</h5>

                <p class="card-text mb-2">

                    {{-- Address --}}
                    @if($company->address || $company->city || $company->state || $company->zip)
                        <strong>Address:</strong><br>
                        {{ $company->address_line1 }}<br>
                        {{ $company->address_line2 }}<br>
                        {{ $company->city }}, {{ $company->state }} {{ $company->zip }}
                    @endif

                    {{-- Website --}}
                    @if($company->website)
                        <br><strong>Website:</strong><br>
                        <a href="{{ $company->website }}" target="_blank">
                            {{ $company->website }}
                        </a>
                    @endif

                    {{-- Phone --}}
                    @if($company->phone)
                        <br><strong>Phone:</strong><br>
                        {{ $company->phone }}
                    @endif

                    {{-- Email --}}
                    @if($company->email)
                       <br><strong>Email:</strong><br>
                        <a href="mailto:{{ $company->email }}">
                            {{ $company->email }}
                        </a>
                    @endif

                </p>
            </div>
        </div>
    @endforeach

@endsection
