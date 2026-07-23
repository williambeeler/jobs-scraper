<?php

namespace App\Http\Controllers;

use App\Enums\JobListingOrigin;
use App\Enums\KanbanStatus;
use App\Models\JobListing;
use App\Services\Kanban\TransitionJobListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomJobController extends Controller
{
    public function __invoke(Request $request, TransitionJobListing $transition): RedirectResponse
    {
        $validated = $request->validateWithBag('customJob', [
            'title' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'source' => ['required', 'string', 'max:32'],
            'posted_at' => ['nullable', 'date_format:Y-m-d'],
            'salary' => ['nullable', 'string', 'max:255'],
        ]);

        $urlHash = hash('sha256', $validated['url']);

        if (JobListing::query()->where('source', $validated['source'])->where('url_hash', $urlHash)->exists()) {
            $exception = ValidationException::withMessages([
                'url' => 'A job with this source and URL already exists.',
            ]);
            $exception->errorBag = 'customJob';

            throw $exception;
        }

        $now = now();
        $listing = JobListing::query()->create([
            ...$validated,
            'listing_origin' => JobListingOrigin::Custom,
            'company_key' => JobListing::companyKey($validated['company'] ?? null),
            'url_hash' => $urlHash,
            'kanban_status' => KanbanStatus::Backlog,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ]);

        $transition->handle($listing, KanbanStatus::Interested);

        return redirect()->route('kanban')->with('status', 'Custom job added.');
    }
}
