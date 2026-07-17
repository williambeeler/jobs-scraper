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
            $table->string('company_key')->nullable()->after('company')->index();
        });

        DB::table('job_listings')->orderBy('id')->each(function (object $listing): void {
            DB::table('job_listings')->where('id', $listing->id)->update([
                'company_key' => $listing->company === null ? null : mb_strtolower(trim(preg_replace('/\s+/', ' ', $listing->company))),
            ]);
        });

        Schema::create('company_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_key')->unique();
            $table->string('type', 16)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_preferences');
        Schema::table('job_listings', fn (Blueprint $table) => $table->dropColumn('company_key'));
    }
};
