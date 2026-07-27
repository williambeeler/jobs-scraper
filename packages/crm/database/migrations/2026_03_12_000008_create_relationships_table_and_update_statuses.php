<?php

use WilliamBeeler\Crm\Support\TableNames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(TableNames::get('statuses'), function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });

        Schema::create(TableNames::get('relationships'), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description');
            $table->timestamps();
        });

        Schema::table(TableNames::get('companies'), function (Blueprint $table) {
            $table->foreignId('relationship_id')->nullable()->after('status_id')->constrained(TableNames::get('relationships'))->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table(TableNames::get('companies'), function (Blueprint $table) {
            $table->dropConstrainedForeignId('relationship_id');
        });

        Schema::dropIfExists(TableNames::get('relationships'));

        Schema::table(TableNames::get('statuses'), function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
