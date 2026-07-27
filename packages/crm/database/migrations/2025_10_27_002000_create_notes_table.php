<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use WilliamBeeler\Crm\Support\TableNames;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(TableNames::get('notes'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained(TableNames::get('companies'))->cascadeOnDelete();
            $table->text('content');
            $table->softDeletes(); // adds nullable timestamp 'deleted_at'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(TableNames::get('notes'));
    }
};
