<?php

namespace WilliamBeeler\Crm\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use WilliamBeeler\Crm\Models\BusinessProfile;
use WilliamBeeler\Crm\Support\Avery94200Pdf;
use WilliamBeeler\Crm\Support\TableNames;

class MyBusinessController extends Controller
{
    public function index(Request $request)
    {
        $businessProfiles = BusinessProfile::query()
            ->orderByDesc('is_default')
            ->orderBy('business_name')
            ->get();

        $activeTab = $request->query('tab', 'address');
        $mode = $request->query('mode', 'edit');

        if (! in_array($activeTab, ['address', 'labels'], true)) {
            $activeTab = 'address';
        }

        if (! in_array($mode, ['edit', 'create'], true)) {
            $mode = 'edit';
        }

        $selectedBusiness = null;

        if ($mode === 'edit') {
            $selectedBusinessId = (int) $request->query('business_id', 0);

            if ($selectedBusinessId > 0) {
                $selectedBusiness = $businessProfiles->firstWhere('id', $selectedBusinessId);
            }

            if (! $selectedBusiness) {
                $selectedBusiness = $businessProfiles->firstWhere('is_default', true) ?? $businessProfiles->first();
            }

            if (! $selectedBusiness) {
                $mode = 'create';
            }
        }

        $selectedLabelBusiness = null;
        $selectedLabelBusinessId = (int) $request->query('label_business_id', 0);

        if ($selectedLabelBusinessId > 0) {
            $selectedLabelBusiness = $businessProfiles->firstWhere('id', $selectedLabelBusinessId);
        }

        if (! $selectedLabelBusiness) {
            $selectedLabelBusiness = $businessProfiles->firstWhere('is_default', true) ?? $businessProfiles->first();
        }

        return view('crm::my-business.index', [
            'activeTab' => $activeTab,
            'mode' => $mode,
            'businessProfiles' => $businessProfiles
                ->map(fn (BusinessProfile $businessProfile): array => $this->transformBusinessProfile($businessProfile))
                ->values()
                ->all(),
            'selectedBusiness' => $selectedBusiness ? $this->transformBusinessProfile($selectedBusiness) : null,
            'selectedLabelBusiness' => $selectedLabelBusiness ? $this->transformBusinessProfile($selectedLabelBusiness) : null,
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $this->validatedAddressData($request);
        $isDefaultRequest = $request->boolean('is_default');

        DB::transaction(function () use ($validated, $isDefaultRequest): void {
            $existingCount = BusinessProfile::query()->count();
            $isDefault = $isDefaultRequest || $existingCount === 0;

            if ($isDefault) {
                BusinessProfile::query()->where('is_default', true)->update(['is_default' => false]);
            }

            BusinessProfile::query()->create([
                ...$validated,
                'is_default' => $isDefault,
            ]);
        });

        return redirect()
            ->route('crm.my-business.index', ['tab' => 'address'])
            ->with('success', 'Business added successfully.');
    }

    public function updateAddress(Request $request, BusinessProfile $businessProfile): RedirectResponse
    {
        $validated = $this->validatedAddressData($request);
        $isDefaultRequest = $request->boolean('is_default');

        DB::transaction(function () use ($businessProfile, $validated, $isDefaultRequest): void {
            $otherDefaultExists = BusinessProfile::query()
                ->where('id', '!=', $businessProfile->id)
                ->where('is_default', true)
                ->exists();

            $isDefault = $isDefaultRequest || ! $otherDefaultExists;

            if ($isDefault) {
                BusinessProfile::query()->where('is_default', true)->update(['is_default' => false]);
            }

            $businessProfile->update([
                ...$validated,
                'is_default' => $isDefault,
            ]);
        });

        return redirect()
            ->route('crm.my-business.index', ['tab' => 'address', 'business_id' => $businessProfile->id])
            ->with('success', 'Business updated successfully.');
    }

    public function destroy(BusinessProfile $businessProfile): RedirectResponse
    {
        DB::transaction(function () use ($businessProfile): void {
            $wasDefault = (bool) $businessProfile->is_default;
            $businessProfile->delete();

            if ($wasDefault) {
                BusinessProfile::query()
                    ->orderBy('business_name')
                    ->first()
                    ?->update(['is_default' => true]);
            }
        });

        return redirect()
            ->route('crm.my-business.index', ['tab' => 'address'])
            ->with('success', 'Business deleted successfully.');
    }

    public function printLabels(Request $request): Response
    {
        $validated = $request->validate([
            'business_id' => 'nullable|integer|exists:'.TableNames::get('business_profiles').',id',
        ]);

        $business = null;

        if (! empty($validated['business_id'])) {
            $business = BusinessProfile::query()->find($validated['business_id']);
        }

        if (! $business) {
            $business = BusinessProfile::query()
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
        }

        abort_if(! $business, 404, 'No business profile found to print.');

        $line1 = trim((string) $business->business_name);
        $line2 = trim(implode(' ', array_filter([
            $business->street_line_1,
            $business->street_line_2,
        ])));
        $cityStateZip = trim(implode(' ', array_filter([
            trim(implode(', ', array_filter([
                $business->city,
                $business->state,
            ]))),
            $business->postal_code,
        ])));

        $line3 = trim(implode(', ', array_filter([
            $cityStateZip,
            $business->country,
        ])));

        $pdf = Avery94200Pdf::generate($line1, $line2, $line3);

        $fileName = sprintf(
            'avery-94200-%s.pdf',
            Str::slug($business->business_name ?: 'business-labels')
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function validatedAddressData(Request $request): array
    {
        return $request->validate([
            'business_name' => 'required|string|max:255',
            'street_line_1' => 'required|string|max:255',
            'street_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:120',
            'state' => 'required|string|max:120',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:120',
            'is_default' => 'nullable|boolean',
        ]);
    }

    /**
     * @return array{
     *     id: int,
     *     business_name: string,
     *     street_line_1: ?string,
     *     street_line_2: ?string,
     *     city: ?string,
     *     state: ?string,
     *     postal_code: ?string,
     *     country: ?string,
     *     is_default: bool,
     *     label: string
     * }
     */
    private function transformBusinessProfile(BusinessProfile $businessProfile): array
    {
        return [
            'id' => $businessProfile->id,
            'business_name' => (string) $businessProfile->business_name,
            'street_line_1' => $businessProfile->street_line_1,
            'street_line_2' => $businessProfile->street_line_2,
            'city' => $businessProfile->city,
            'state' => $businessProfile->state,
            'postal_code' => $businessProfile->postal_code,
            'country' => $businessProfile->country,
            'is_default' => (bool) $businessProfile->is_default,
            'label' => sprintf(
                '%s%s',
                $businessProfile->business_name ?: 'Untitled Business',
                $businessProfile->is_default ? ' (Default)' : ''
            ),
        ];
    }
}
