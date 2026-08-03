<?php

namespace WilliamBeeler\Crm\Http\Controllers;

use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\PhoneCall;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PhoneCallController extends Controller
{
    public function store(Request $request, Company $company)
    {
        $validated = $request->validate([
            'called_at' => ['required', 'date'],
            'notes' => ['required', 'string'],
        ]);

        $phoneCall = $company->phoneCalls()->create($validated);

        if (!$request->expectsJson()) {
            return redirect()
                ->route('crm.companies.show', $company)
                ->with('success', 'Phone call saved.');
        }

        return response()->json([
            'message' => 'Phone call saved.',
            'phoneCall' => [
                'id' => $phoneCall->id,
                'called_at' => $phoneCall->called_at?->toIso8601String(),
                'called_at_label' => $phoneCall->called_at?->format('M d, Y g:i A') ?? 'No date recorded',
                'notes' => $phoneCall->notes,
            ],
            'phoneCallHtml' => view('crm::companies.partials.phone-call-item', [
                'phoneCall' => PhoneCall::query()->findOrFail($phoneCall->id),
            ])->render(),
        ]);
    }
}
