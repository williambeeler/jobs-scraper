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
        Schema::create(TableNames::get('companies'), function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('poc_name', 150)->nullable();
            $table->string('poc_email', 150)->nullable();
            $table->string('poc_phone', 50)->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable(); //US for now but could be expanded to support international addresses
            $table->string('source', 100)->nullable();
            $table->foreignId('status_id')->nullable()->constrained(TableNames::get('statuses'))->nullOnDelete();
            $table->json('meta')->nullable();
            $table->softDeletes(); // adds nullable timestamp 'deleted_at'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(TableNames::get('companies'));
    }
};
