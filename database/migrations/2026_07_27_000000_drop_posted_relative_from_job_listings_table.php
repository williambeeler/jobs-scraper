<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('job_listings', 'posted_relative')) {
            Schema::table('job_listings', function (Blueprint $table): void {
                $table->dropColumn('posted_relative');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('job_listings', 'posted_relative')) {
            Schema::table('job_listings', function (Blueprint $table): void {
                $table->string('posted_relative')->nullable()->after('posted_at');
            });
        }
    }
};
