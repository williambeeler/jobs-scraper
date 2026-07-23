<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->string('kanban_status', 32)->default('backlog')->after('status_changed_at')->index();
        });

        DB::table('job_listings')
            ->where('status', 'applied')
            ->update(['kanban_status' => 'applied']);
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropIndex(['kanban_status']);
            $table->dropColumn('kanban_status');
        });
    }
};
