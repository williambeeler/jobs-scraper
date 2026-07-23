<?php

namespace App\Http\Controllers;

use App\Enums\KanbanStatus;
use App\Models\JobListing;
use App\Services\Kanban\TransitionJobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KanbanStatusController extends Controller
{
    public function __invoke(
        Request $request,
        JobListing $jobListing,
        TransitionJobListing $transition,
    ): JsonResponse {
        $validated = $request->validate([
            'kanban_status' => ['required', Rule::enum(KanbanStatus::class)],
        ]);

        $kanbanStatus = KanbanStatus::from($validated['kanban_status']);

        $jobListing = $transition->handle($jobListing, $kanbanStatus);

        return response()->json([
            'kanban_status' => $jobListing->kanban_status->value,
            'status' => $jobListing->status?->value,
        ]);
    }
}
