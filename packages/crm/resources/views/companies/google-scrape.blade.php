@extends('crm::layouts.app')
@section('title','Google Places Usage') @section('heading','Google Places Usage') @section('subtitle','Review usage and cost information for company enrichment.')
@section('actions')<a class="crm-button" href="{{ route('companies.index') }}">Companies</a>@endsection
@section('content')<div class="crm-grid">@foreach(get_defined_vars() as $key=>$value)@if(is_scalar($value)&&!str_starts_with($key,'__'))<div class="crm-col-3 crm-card"><div class="crm-muted">{{ str($key)->headline() }}</div><h2>{{ $value }}</h2></div>@endif @endforeach</div>@endsection
