<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use WilliamBeeler\Crm\Support\TableNames;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(TableNames::get('tags'), function (Blueprint $table) {
            $table->id();

            $table->json('name');
            $table->json('slug');
            $table->string('type')->nullable();
            $table->integer('order_column')->nullable();

            $table->timestamps();
        });

        Schema::create(TableNames::get('taggables'), function (Blueprint $table) {
            $table->foreignId('tag_id')
                ->constrained(TableNames::get('tags'))
                ->cascadeOnDelete();

            $table->morphs('taggable');

            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableNames::get('taggables'));
        Schema::dropIfExists(TableNames::get('tags'));
    }
};
