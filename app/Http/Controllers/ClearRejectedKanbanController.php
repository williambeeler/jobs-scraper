<?php

namespace App\Http\Controllers;

use App\Enums\KanbanStatus;
use App\Models\JobListing;
use App\Services\Kanban\TransitionJobListing;
use Illuminate\Http\RedirectResponse;

class ClearRejectedKanbanController extends Controller
{
    public function __invoke(TransitionJobListing $transition): RedirectResponse
    {
        $cleared = 0;

        JobListing::query()
            ->where('kanban_status', KanbanStatus::Rejected)
            ->eachById(function (JobListing $jobListing) use ($transition, &$cleared): void {
                $transition->handle($jobListing, KanbanStatus::RejectedCleared);
                $cleared++;
            });

        $message = $cleared === 1
            ? '1 rejected job cleared from the Kanban.'
            : "{$cleared} rejected jobs cleared from the Kanban.";

        return redirect()->route('jobs.kanban')->with('status', $message);
    }
}
