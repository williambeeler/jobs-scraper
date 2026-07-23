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
            $table->string('listing_origin', 16)->default('scraped')->after('source')->index();
        });

        DB::table('job_listings')->update(['listing_origin' => 'scraped']);
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropIndex(['listing_origin']);
            $table->dropColumn('listing_origin');
        });
    }
};
