<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('job_listings', 'kanban_status_changed_at')) {
            Schema::table('job_listings', function (Blueprint $table) {
                $table->timestamp('kanban_status_changed_at')->nullable()->after('kanban_status');
            });
        }

        // A failed CREATE can leave this untracked table behind on MySQL.
        Schema::dropIfExists('job_listing_kanban_transitions');

        Schema::create('job_listing_kanban_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->timestamp('transitioned_at');
            $table->index(['job_listing_id', 'transitioned_at'], 'kanban_job_time_idx');
            $table->index(['to_status', 'transitioned_at'], 'kanban_status_time_idx');
        });

        DB::table('job_listings')
            ->where('kanban_status', '!=', 'backlog')
            ->orderBy('id')
            ->each(function (object $listing): void {
                $transitionedAt = $listing->kanban_status === 'applied' && $listing->status_changed_at
                    ? $listing->status_changed_at
                    : $listing->updated_at;

                DB::table('job_listings')->where('id', $listing->id)->update([
                    'kanban_status_changed_at' => $transitionedAt,
                ]);

                DB::table('job_listing_kanban_transitions')->insert([
                    'job_listing_id' => $listing->id,
                    'from_status' => null,
                    'to_status' => $listing->kanban_status,
                    'transitioned_at' => $transitionedAt,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listing_kanban_transitions');

        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn('kanban_status_changed_at');
        });
    }
};
