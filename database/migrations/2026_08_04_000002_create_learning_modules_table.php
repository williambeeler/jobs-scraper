<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_modules', function (Blueprint $table) {
            $table->id();
            $table->morphs('moduleable');
            $table->string('title');
            $table->string('url', 2048)->nullable();
            $table->longText('description')->nullable();
            $table->date('completion_date')->nullable();
            $table->boolean('is_completed')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_modules');
    }
};
