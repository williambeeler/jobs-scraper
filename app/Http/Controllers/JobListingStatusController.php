<?php

namespace App\Http\Controllers;

use App\Enums\JobListingStatus;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobListingStatusController extends Controller
{
    public function __invoke(Request $request, JobListing $jobListing): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(JobListingStatus::class)],
        ]);

        $jobListing->update([
            'status' => $validated['status'],
            'status_changed_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $jobListing->status?->value,
            ]);
        }

        return back();
    }
}
