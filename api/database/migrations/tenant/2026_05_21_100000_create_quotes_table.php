<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')
                ->constrained('clients')
                ->restrictOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default('draft');
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('client_tax_id', 50)->nullable();
            $table->string('client_address', 500)->nullable();
            $table->string('currency', 3);
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('client_id');
            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
