<?php

namespace App\Services\Kanban;

use App\Enums\JobListingStatus;
use App\Enums\KanbanStatus;
use App\Models\JobListing;
use Illuminate\Support\Facades\DB;

class TransitionJobListing
{
    public function handle(JobListing $jobListing, KanbanStatus $destination): JobListing
    {
        return DB::transaction(function () use ($jobListing, $destination): JobListing {
            $origin = $jobListing->kanban_status;
            $transitionedAt = now();
            $updates = [];

            if ($origin !== $destination) {
                $updates['kanban_status'] = $destination;
                $updates['kanban_status_changed_at'] = $transitionedAt;
            }

            if ($destination === KanbanStatus::Applied && $jobListing->status !== JobListingStatus::Applied) {
                $updates['status'] = JobListingStatus::Applied;
                $updates['status_changed_at'] = $transitionedAt;
            }

            if ($updates !== []) {
                $jobListing->update($updates);
            }

            if ($origin !== $destination) {
                $jobListing->kanbanTransitions()->create([
                    'from_status' => $origin,
                    'to_status' => $destination,
                    'transitioned_at' => $transitionedAt,
                ]);
            }

            return $jobListing->refresh();
        });
    }
}
