<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->unique()
                ->constrained('projects')
                ->restrictOnDelete();
            $table->foreignId('client_id')
                ->constrained('clients')
                ->restrictOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default('issued');
            $table->string('project_name');
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('client_tax_id', 50)->nullable();
            $table->string('client_address', 500)->nullable();
            $table->string('currency', 3);
            $table->unsignedBigInteger('agreed_total_cents');
            $table->unsignedBigInteger('paid_total_cents')->default(0);
            $table->unsignedBigInteger('balance_due_cents');
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_documents');
    }
};
