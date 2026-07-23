<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_topics', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('planned')->index();
            $table->string('priority')->default('medium')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_topics');
    }
};
