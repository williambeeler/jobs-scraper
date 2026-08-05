<?php

use App\Crm\Support\TableNames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(TableNames::get('companies'), function (Blueprint $table) {
            $table->unsignedTinyInteger('opportunity_score_index')->nullable()->after('status_id');
            $table->longText('opportunity_summary')->nullable()->after('opportunity_score_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(TableNames::get('companies'), function (Blueprint $table) {
            $table->dropColumn(['opportunity_score_index', 'opportunity_summary']);
        });
    }
};
