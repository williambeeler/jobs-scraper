<?php

use WilliamBeeler\Crm\Support\TableNames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(TableNames::get('companies'), function (Blueprint $table) {
            $table->boolean('needs_callback')->default(false)->after('phone');
            $table->dateTime('callback_at')->nullable()->after('needs_callback');
        });
    }

    public function down(): void
    {
        Schema::table(TableNames::get('companies'), function (Blueprint $table) {
            $table->dropColumn(['needs_callback', 'callback_at']);
        });
    }
};
