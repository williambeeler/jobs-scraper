<?php

use App\Crm\Support\TableNames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(TableNames::get('google_places_usage_logs'), function (Blueprint $table) {
            $table->id();
            $table->date('usage_date')->index();
            $table->string('tier')->index();
            $table->unsignedInteger('google_request_count')->default(0);
            $table->unsignedInteger('points_used')->default(0);
            $table->unsignedInteger('search_terms_count')->default(0);
            $table->unsignedInteger('radius_miles')->default(0);
            $table->unsignedInteger('max_companies')->default(0);
            $table->unsignedInteger('imported_companies_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableNames::get('google_places_usage_logs'));
    }
};
