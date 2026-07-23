@extends('layouts.app')

@section('title', 'Jobs Calendar | '.config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-secondary fw-semibold small">Tools</p>
            <h1 class="display-5 fw-bold mb-0">Jobs Calendar</h1>
        </header>

        <div class="bg-white border rounded shadow-sm p-3 p-md-4">
            <div
                id="calendar"
                data-jobs-url="{{ route('jobs') }}"
                data-counts-url="{{ route('calendar.counts') }}"
            ></div>
        </div>
    </main>
@endsection

@push('styles')
    <style>
        #calendar .fc-daygrid-day {
            cursor: pointer;
        }

        #calendar .fc-daygrid-day:hover {
            background-color: rgba(13, 110, 253, .06);
        }

        #calendar .fc-daygrid-day-events {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        #calendar .fc-daygrid-event-harness {
            flex: 0 0 auto;
        }

        #calendar .fc-daygrid-event {
            --fc-event-text-color: #000;
            display: inline-block;
            width: fit-content;
            border: 0;
            border-radius: 999px;
            margin: 2px 4px;
            padding: 2px 7px;
            color: #000;
            font-size: .75rem;
            font-weight: 600;
        }

        #calendar .fc-daygrid-event .fc-event-main {
            color: #000;
        }

        #calendar .calendar-total-count {
            background: #e9ecef;
            color: #000;
        }

        #calendar .calendar-whitelist-count {
            background: #fff3cd;
            color: #000;
        }
    </style>
@endpush

@push('scripts')
    @vite('resources/js/calendar.ts')
@endpush
