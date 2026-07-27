<?php

namespace App\Http\Controllers;

use App\Enums\CompanyPreferenceType;
use App\Enums\JobListingOrigin;
use App\Enums\JobListingStatus;
use App\Models\Company;
use App\Models\CompanyPreference;
use App\Models\JobListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyPreferenceController extends Controller
{
    public function index(): View
    {
        return view('company-preferences', [
            'preferences' => CompanyPreference::query()
                ->with('company')
                ->orderBy('type')
                ->orderBy(Company::query()->select('name')->whereColumn('companies.id', 'company_preferences.company_id'))
                ->get(),
        ]);
    }

    public function store(Request $request, JobListing $jobListing): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(CompanyPreferenceType::class)],
        ]);
        abort_if($jobListing->company_id === null, 422, 'This listing has no company name.');

        $type = CompanyPreferenceType::from($validated['type']);
        CompanyPreference::query()->updateOrCreate(
            ['company_id' => $jobListing->company_id],
            ['type' => $type],
        );

        if ($type === CompanyPreferenceType::Blacklist) {
            JobListing::query()
                ->where('listing_origin', JobListingOrigin::Scraped->value)
                ->where('company_id', $jobListing->company_id)
                ->update([
                    'status' => JobListingStatus::NotInterested,
                    'status_changed_at' => now(),
                ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['type' => $type->value]);
        }

        return back();
    }

    public function destroy(CompanyPreference $companyPreference): RedirectResponse
    {
        $companyPreference->delete();

        return back()->with('status', 'Company preference removed.');
    }
}
