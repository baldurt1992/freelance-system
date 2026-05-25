<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')
                ->constrained('clients')
                ->restrictOnDelete();
            $table->foreignId('quote_id')
                ->nullable()
                ->unique()
                ->constrained('quotes')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('type')->default('freelance');
            $table->string('status')->default('active');
            $table->string('quote_number')->nullable();
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('client_tax_id', 50)->nullable();
            $table->string('client_address', 500)->nullable();
            $table->string('currency', 3);
            $table->unsignedBigInteger('agreed_total_cents')->default(0);
            $table->unsignedBigInteger('paid_total_cents')->default(0);
            $table->unsignedBigInteger('balance_due_cents')->default(0);
            $table->boolean('is_fully_paid')->default(false);
            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('client_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
