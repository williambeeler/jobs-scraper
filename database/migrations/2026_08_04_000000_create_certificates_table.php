<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_topic_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url', 2048)->nullable();
            $table->longText('description')->nullable();
            $table->longText('study_tips')->nullable();
            $table->date('completion_date')->nullable();
            $table->boolean('is_completed')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
