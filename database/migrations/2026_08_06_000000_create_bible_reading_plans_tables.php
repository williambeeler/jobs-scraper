<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_reading_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('duration_years')->default(1);
            $table->timestamps();
        });

        Schema::create('bible_reading_plan_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bible_reading_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('cycle_year')->default(1);
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('month_day');
            $table->unsignedSmallInteger('day_number')->nullable();
            $table->boolean('is_catch_up')->default(false);
            $table->timestamps();
            $table->unique(['bible_reading_plan_id', 'cycle_year', 'month', 'month_day'], 'bible_plan_calendar_unique');
        });

        Schema::create('bible_reading_plan_passages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bible_reading_plan_day_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('book_id');
            $table->string('book_slug');
            $table->unsignedTinyInteger('start_chapter');
            $table->unsignedSmallInteger('start_verse')->nullable();
            $table->unsignedTinyInteger('end_chapter');
            $table->unsignedSmallInteger('end_verse')->nullable();
            $table->string('reference');
            $table->unsignedTinyInteger('display_order');
            $table->timestamps();
        });

        Schema::create('bible_reading_plan_completions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bible_reading_plan_day_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('reading_year');
            $table->timestamp('completed_at');
            $table->timestamps();
            $table->unique(['bible_reading_plan_day_id', 'reading_year'], 'bible_plan_completion_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_reading_plan_completions');
        Schema::dropIfExists('bible_reading_plan_passages');
        Schema::dropIfExists('bible_reading_plan_days');
        Schema::dropIfExists('bible_reading_plans');
    }
};
