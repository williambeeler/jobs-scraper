<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();
            $table->longText('question');
            $table->longText('answer');
            $table->string('difficulty')->default('medium')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_cards');
    }
};
