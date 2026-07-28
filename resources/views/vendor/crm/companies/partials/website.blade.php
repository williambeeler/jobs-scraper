@if($company->website)
    <a href="{{ $company->website }}" target="_blank">{{ parse_url($company->website, PHP_URL_HOST) }}</a>
@else
    —
@endif