<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_key')->unique();
            $table->timestamps();
        });

        Schema::table('job_listings', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('title')->index();
        });
        Schema::table('company_preferences', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
        });

        $now = now();
        $companies = DB::table('job_listings')
            ->whereNotNull('company_key')
            ->select('company', 'company_key')
            ->union(DB::table('company_preferences')->select('company_name as company', 'company_key'))
            ->orderBy('company_key')
            ->get();

        foreach ($companies as $company) {
            DB::table('companies')->updateOrInsert(
                ['name_key' => $company->company_key],
                ['name' => $company->company, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        DB::table('job_listings')->whereNotNull('company_key')->orderBy('id')->each(function (object $listing): void {
            DB::table('job_listings')->where('id', $listing->id)->update([
                'company_id' => DB::table('companies')->where('name_key', $listing->company_key)->value('id'),
            ]);
        });
        DB::table('company_preferences')->orderBy('id')->each(function (object $preference): void {
            DB::table('company_preferences')->where('id', $preference->id)->update([
                'company_id' => DB::table('companies')->where('name_key', $preference->company_key)->value('id'),
            ]);
        });

        if (DB::table('company_preferences')->whereNull('company_id')->exists()) {
            throw new RuntimeException('Unable to connect every company preference to a company.');
        }

        Schema::table('job_listings', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->dropIndex(['company_key']);
            $table->dropColumn(['company', 'company_key']);
        });
        Schema::table('company_preferences', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique('company_id');
            $table->dropUnique(['company_key']);
            $table->dropColumn(['company_name', 'company_key']);
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->string('company')->nullable()->after('title');
            $table->string('company_key')->nullable()->after('company')->index();
        });
        Schema::table('company_preferences', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('id');
            $table->string('company_key')->nullable()->after('company_name');
        });

        DB::table('job_listings')->whereNotNull('company_id')->orderBy('id')->each(function (object $listing): void {
            $company = DB::table('companies')->find($listing->company_id);
            DB::table('job_listings')->where('id', $listing->id)->update([
                'company' => $company?->name,
                'company_key' => $company?->name_key,
            ]);
        });
        DB::table('company_preferences')->orderBy('id')->each(function (object $preference): void {
            $company = DB::table('companies')->find($preference->company_id);
            DB::table('company_preferences')->where('id', $preference->id)->update([
                'company_name' => $company?->name,
                'company_key' => $company?->name_key,
            ]);
        });

        Schema::table('company_preferences', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropUnique(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
            $table->unique('company_key');
        });
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
        Schema::dropIfExists('companies');
    }
};
