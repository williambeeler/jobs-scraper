@extends('crm::layouts.app')
@section('title','Companies')
@section('heading','Companies')
@section('subtitle','Track accounts, assign tags, and manage outreach priorities.')
@section('actions')<a class="crm-button crm-button-primary" href="{{ route('companies.create') }}">Add Company</a>@endsection
@section('content')
<form class="crm-card" method="GET" action="{{ route('companies.index') }}">
    <div class="crm-grid">
        <div class="crm-col-4 crm-field"><label for="search">Search</label><input class="crm-input" id="search" name="search" value="{{ request('search') }}" placeholder="Company, contact, website, email"></div>
        <div class="crm-col-3 crm-field"><label for="status_id">Status</label><select class="crm-select" id="status_id" name="status_id"><option value="">All statuses</option>@foreach($statusOptions as $status)<option value="{{ $status['id'] }}" @selected((string)request('status_id')===$status['id'])>{{ $status['name'] }}</option>@endforeach</select></div>
        <div class="crm-col-3 crm-field"><label for="created_range">Created</label><select class="crm-select" id="created_range" name="created_range">@foreach(['all'=>'Any time','last_30'=>'Last 30 days','last_90'=>'Last 90 days','this_year'=>'This year'] as $value=>$label)<option value="{{ $value }}" @selected(request('created_range','all')===$value)>{{ $label }}</option>@endforeach</select></div>
        <div class="crm-col-2 crm-field"><label for="per_page">Per page</label><select class="crm-select" id="per_page" name="per_page">@foreach([20,50,100] as $size)<option @selected((int)request('per_page',20)===$size)>{{ $size }}</option>@endforeach</select></div>
        <div class="crm-col-4 crm-field"><label for="tag_filter">Tag</label><select class="crm-select" id="tag_filter" name="tag_filter"><option value="">All tags</option>@foreach($tagOptions as $tag)<option value="{{ $tag['name'] }}" @selected(request('tag_filter')===$tag['name'])>{{ $tag['name'] }}</option>@endforeach</select></div>
        <div class="crm-col-4 crm-field"><label for="campaign_filter">Campaign</label><select class="crm-select" id="campaign_filter" name="campaign_filter"><option value="">All campaigns</option>@foreach($campaignOptions as $campaign)<option value="{{ $campaign['name'] }}" @selected(request('campaign_filter')===$campaign['name'])>{{ $campaign['name'] }}</option>@endforeach</select></div>
        <div class="crm-col-4 crm-actions"><button class="crm-button crm-button-primary">Search</button><a class="crm-button" href="{{ route('companies.index') }}">Reset</a></div>
    </div>
</form>
<form class="crm-card" method="POST" action="{{ route('companies.bulk-action') }}">@csrf
    <input type="hidden" name="search_term" value="{{ request('search') }}"><input type="hidden" name="status_id" value="{{ request('status_id') }}"><input type="hidden" name="tag_filter" value="{{ request('tag_filter') }}"><input type="hidden" name="campaign_filter" value="{{ request('campaign_filter') }}"><input type="hidden" name="created_range" value="{{ request('created_range','all') }}">
    <div class="crm-actions"><select class="crm-select" style="width:auto" name="bulk_action"><option value="">Bulk action</option><option value="export_csv">Export CSV</option><option value="export_excel">Export Excel</option><option value="export_pdf">Export PDF</option><option value="export_vcard">Export vCard</option><option value="bulk_delete">Archive</option></select><select class="crm-select" style="width:auto" name="scope"><option value="selected">Selected</option><option value="filtered">All filtered</option></select><button class="crm-button">Apply</button><span class="crm-muted">{{ $companies->firstItem() ?? 0 }}–{{ $companies->lastItem() ?? 0 }} of {{ number_format($companies->total()) }}</span></div>
    <div class="crm-table-wrap"><table class="crm-table"><thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('[name=\'companies[]\']').forEach(x=>x.checked=this.checked)"></th><th>Company</th><th>Tags</th><th>Status</th><th>Score</th><th>Location</th><th></th></tr></thead><tbody>
    @forelse($companies as $company)<tr><td><input type="checkbox" name="companies[]" value="{{ $company->id }}"></td><td><a href="{{ route('companies.show',$company) }}">{{ $company->name }}</a><br><span class="crm-muted">{{ $company->website }}</span></td><td>@foreach($company->tags->where('type','tag') as $tag)<span class="crm-badge">{{ $tag->name }}</span>@endforeach @foreach($company->tags->where('type','campaign') as $tag)<span class="crm-badge crm-badge-aqua">{{ $tag->name }}</span>@endforeach</td><td>{{ $company->status?->name ?? '—' }}</td><td>{{ $company->opportunity_score_index ?? '—' }}</td><td>{{ implode(', ',array_filter([$company->city,$company->state])) ?: '—' }}</td><td><a href="{{ route('companies.edit',$company) }}">Edit</a></td></tr>@empty<tr><td colspan="7" class="crm-empty">No companies match these filters.</td></tr>@endforelse
    </tbody></table></div>
    <div class="crm-pagination">{!! $companies->onEachSide(1)->links() !!}</div>
</form>
@endsection
