@foreach ($company->tagsWithType('tag') as $tag)
    <a href="{{ route('companies.index', ['tag_filter' => $tag->name]) }}"
        class="badge rounded-pill crm-tag-badge text-decoration-none">
        {{ $tag->name }}
    </a>
@endforeach
