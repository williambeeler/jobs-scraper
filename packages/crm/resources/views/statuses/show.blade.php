@extends('crm::layouts.app')
@section('title',$status['name']) @section('heading',$status['name']) @section('subtitle','System-defined company status.')
@section('actions')<a class="crm-button" href="{{ route('statuses.index') }}">Back to Statuses</a>@endsection
@section('content')<section class="crm-card"><dl class="crm-definition"><dt>Slug</dt><dd>{{ $status['slug'] }}</dd><dt>Description</dt><dd>{{ $status['description'] ?: '—' }}</dd><dt>Color</dt><dd><span class="crm-badge" style="background:{{ $status['color'] ?: '#e2e8f0' }}">{{ $status['color'] ?: 'Default' }}</span></dd><dt>Default</dt><dd>{{ $status['is_default']?'Yes':'No' }}</dd></dl></section>@endsection
