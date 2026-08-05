<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_feeds', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('url');
            $table->string('url_hash', 64)->unique();
            $table->text('site_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('news_articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('news_feed_id')->constrained()->cascadeOnDelete();
            $table->string('guid_hash', 64);
            $table->string('title');
            $table->text('url');
            $table->string('author')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['news_feed_id', 'guid_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_articles');
        Schema::dropIfExists('news_feeds');
    }
};
