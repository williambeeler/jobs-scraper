<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrape_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('keywords');
            $table->json('sources');
            $table->char('configuration_hash', 64)->unique();
            $table->unsignedSmallInteger('max_days')->default(5);
            $table->unsignedSmallInteger('interval_hours')->default(6);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_ran_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
        });

        Schema::table('scrape_runs', function (Blueprint $table) {
            $table->foreignId('scrape_schedule_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scrape_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scrape_schedule_id');
        });

        Schema::dropIfExists('scrape_schedules');
    }
};
