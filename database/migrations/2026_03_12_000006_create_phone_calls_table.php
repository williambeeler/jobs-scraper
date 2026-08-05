<?php

use App\Crm\Support\TableNames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(TableNames::get('phone_calls'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained(TableNames::get('companies'))->cascadeOnDelete();
            $table->dateTime('called_at');
            $table->longText('notes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableNames::get('phone_calls'));
    }
};
