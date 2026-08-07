<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JobListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobListingDetailsController extends Controller
{
    public function __invoke(Request $request, JobListing $jobListing): RedirectResponse
    {
        $validated = $request->validateWithBag('listingDetails', [
            'listing_id' => ['sometimes', 'integer', Rule::in([$jobListing->id])],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'url' => ['sometimes', 'required', 'url:http,https', 'max:2048'],
            'source' => ['sometimes', 'required', 'string', 'max:32'],
            'posted_at' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'salary' => ['nullable', 'string', 'max:255'],
        ]);

        $urlHash = hash('sha256', $validated['url'] ?? $jobListing->url);
        $source = $validated['source'] ?? $jobListing->source;
        Validator::make(
            ['url_hash' => $urlHash],
            ['url_hash' => [Rule::unique('job_listings')->where('source', $source)->ignore($jobListing)]],
            ['url_hash.unique' => 'This source already has a job with that URL.'],
        )->validateWithBag('listingDetails');

        $company = array_key_exists('company', $validated)
            ? Company::findOrCreateByName($validated['company'])
            : null;
        $companyWasUpdated = array_key_exists('company', $validated);
        unset($validated['listing_id'], $validated['company']);

        $jobListing->fill($validated);
        if ($companyWasUpdated) {
            $jobListing->company()->associate($company);
        }
        $jobListing->url_hash = $urlHash;
        $jobListing->save();

        return back()->with('status', 'Job details updated.');
    }
}
