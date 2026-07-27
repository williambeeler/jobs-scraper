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
        Schema::create(TableNames::get('saved_searches'), function (Blueprint $table) {
            $table->id();
            $table->string('resource', 50)->index();
            $table->string('name');
            $table->string('parameter_hash', 64)->index();
            $table->json('parameters');
            $table->timestamps();

            $table->unique(['resource', 'parameter_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(TableNames::get('saved_searches'));
    }
};
