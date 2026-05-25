<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->unsignedBigInteger('amount_cents');
            $table->date('occurred_on');
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->boolean('is_manual')->default(true);
            $table->timestamps();

            $table->index('occurred_on');
            $table->index(['type', 'occurred_on']);
            $table->unique(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_entries');
    }
};
