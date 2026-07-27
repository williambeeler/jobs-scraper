<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use WilliamBeeler\Crm\Support\TableNames;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(TableNames::get('business_profiles'), function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('country');
        });

        $firstId = DB::table(TableNames::get('business_profiles'))->orderBy('id')->value('id');

        if ($firstId !== null) {
            DB::table(TableNames::get('business_profiles'))->where('id', $firstId)->update(['is_default' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(TableNames::get('business_profiles'), function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
