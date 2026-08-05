<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_feeds', function (Blueprint $table): void {
            $table->text('icon_url')->nullable()->after('site_url');
        });

        Schema::table('news_articles', function (Blueprint $table): void {
            $table->text('image_url')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table): void {
            $table->dropColumn('image_url');
        });

        Schema::table('news_feeds', function (Blueprint $table): void {
            $table->dropColumn('icon_url');
        });
    }
};
