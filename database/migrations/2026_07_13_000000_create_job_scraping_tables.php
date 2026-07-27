<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrape_runs', function (Blueprint $table) {
            $table->id();
            $table->string('keywords');
            $table->unsignedSmallInteger('max_days');
            $table->json('sources');
            $table->string('status')->default('running');
            $table->unsignedInteger('jobs_found')->default(0);
            $table->unsignedInteger('jobs_created')->default(0);
            $table->unsignedInteger('jobs_updated')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32);
            $table->string('title');
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->text('url');
            $table->char('url_hash', 64);
            $table->date('posted_at')->nullable();
            $table->string('salary')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['source', 'url_hash']);
            $table->index(['posted_at', 'source']);
        });

        Schema::create('job_listing_scrape_run', function (Blueprint $table) {
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scrape_run_id')->constrained()->cascadeOnDelete();
            $table->primary(['job_listing_id', 'scrape_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listing_scrape_run');
        Schema::dropIfExists('job_listings');
        Schema::dropIfExists('scrape_runs');
    }
};
