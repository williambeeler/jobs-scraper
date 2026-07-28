@foreach ($company->tagsWithType('campaign') as $campaign)
    <a href="{{ route('companies.index', ['campaign_filter' => $campaign->name]) }}"
        class="badge rounded-pill bg-info text-dark text-decoration-none">
        {{ $campaign->name }}
    </a>
@endforeach
