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
        Schema::create(TableNames::get('statuses'), function (Blueprint $table) {
            $table->id();
            $table->string('name');           // e.g. Active, Prospect, On Hold
            $table->string('slug')->unique(); // e.g. active, prospect, on-hold
            $table->string('color')->nullable(); // optional color hex for UI
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(TableNames::get('statuses'));
    }
};
