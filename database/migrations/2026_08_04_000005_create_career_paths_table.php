<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_paths', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('career_path_learning_topic', function (Blueprint $table) {
            $table->foreignId('career_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_topic_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['career_path_id', 'learning_topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_path_learning_topic');
        Schema::dropIfExists('career_paths');
    }
};
