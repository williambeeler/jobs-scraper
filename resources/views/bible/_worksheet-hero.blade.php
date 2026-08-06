@php
    $completionPercent =
        $totalChaptersCount > 0
            ? (int) round(($completedChaptersCount / $totalChaptersCount) * 100)
            : 0;
@endphp

<section class="bible-hero">
    <div>
        <div class="bible-hero-kicker">Bible Study Worksheets</div>
        <div class="bible-hero-year-wrap">
            <a
                class="bible-year-bumper"
                href="{{ route('bible.worksheets', ['year' => $displayYear - 1]) }}"
                aria-label="Show {{ $displayYear - 1 }} worksheets"
            >
                <span aria-hidden="true">&lsaquo;</span>
            </a>

            <div>
                <h1 class="bible-hero-year">{{ $displayYear }}</h1>
                <p class="bible-hero-subtitle mb-0">
                    {{ $completedChaptersCount }} of {{ $totalChaptersCount }} chapters completed
                </p>
            </div>

            <a
                class="bible-year-bumper"
                href="{{ route('bible.worksheets', ['year' => $displayYear + 1]) }}"
                aria-label="Show {{ $displayYear + 1 }} worksheets"
            >
                <span aria-hidden="true">&rsaquo;</span>
            </a>
        </div>
    </div>

    <div class="bible-progress-panel">
        <div class="bible-progress-label">Year progress</div>
        <div class="bible-progress-value" data-bible-progress>{{ $completionPercent }}%</div>
        <div class="bible-progress-bar" aria-hidden="true">
            <span style="width: {{ $completionPercent }}%"></span>
        </div>
        <div class="bible-progress-footnote" data-bible-feedback>
            {{ $displayYear === $currentYear ? 'Current year loaded by default.' : 'Use the Bible link to jump back to the current year.' }}
        </div>
    </div>
</section>
