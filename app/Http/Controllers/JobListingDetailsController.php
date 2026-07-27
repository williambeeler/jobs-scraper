<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JobListingDetailsController extends Controller
{
    public function __invoke(Request $request, JobListing $jobListing): RedirectResponse
    {
        $validated = $request->validateWithBag('listingDetails', [
            'salary' => ['nullable', 'string', 'max:255'],
        ]);

        $jobListing->update($validated);

        return back()->with('status', 'Salary updated.');
    }
}
