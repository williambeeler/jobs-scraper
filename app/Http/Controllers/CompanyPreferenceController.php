<?php

namespace App\Http\Controllers;

use App\Enums\CompanyPreferenceType;
use App\Enums\JobListingStatus;
use App\Models\CompanyPreference;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

class CompanyPreferenceController extends Controller
{
    public function index(): View
    {
        return view('company-preferences', [
            'preferences' => CompanyPreference::query()->orderBy('type')->orderBy('company_name')->get(),
        ]);
    }

    public function store(Request $request, JobListing $jobListing): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(CompanyPreferenceType::class)],
        ]);
        abort_if($jobListing->company_key === null, 422, 'This listing has no company name.');

        $type = CompanyPreferenceType::from($validated['type']);
        CompanyPreference::query()->updateOrCreate(
            ['company_key' => $jobListing->company_key],
            ['company_name' => $jobListing->company, 'type' => $type],
        );

        if ($type === CompanyPreferenceType::Blacklist) {
            JobListing::query()->where('company_key', $jobListing->company_key)->update([
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
