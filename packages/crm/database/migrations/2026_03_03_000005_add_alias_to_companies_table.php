<?php

use WilliamBeeler\Crm\Support\TableNames;
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
            $table->string('alias')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(TableNames::get('companies'), function (Blueprint $table) {
            $table->dropColumn('alias');
        });
    }
};
