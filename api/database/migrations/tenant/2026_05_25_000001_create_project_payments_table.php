<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('kind');
            $table->date('paid_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_id', 'paid_at']);
            $table->index(['project_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_payments');
    }
};
