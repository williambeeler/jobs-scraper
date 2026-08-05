@php
    $score = $company->opportunity_score_index;
@endphp

@if (is_null($score))
    <span class="badge rounded-pill bg-secondary-subtle text-secondary border">N/A</span>
@else
    @php
        $normalized = max(0, min(100, (int) $score));
        // 0 => red, 100 => green
        $hue = (int) round(($normalized / 100) * 120);
        $bgColor = "hsl({$hue} 72% 92%)";
        $textColor = "hsl({$hue} 78% 28%)";
        $borderColor = "hsl({$hue} 55% 72%)";
    @endphp
    <span class="badge rounded-pill px-3 py-2 fw-semibold"
        style="background-color: {{ $bgColor }}; color: {{ $textColor }}; border: 1px solid {{ $borderColor }};"
    >
        {{ $normalized }}
    </span>
@endif
