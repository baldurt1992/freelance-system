<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->restrictOnDelete();
            $table->string('name');
            $table->longText('html_body');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['type', 'client_id']);
            $table->index(['type', 'is_default']);
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }

    private function seedDefaults(): void
    {
        $now = now();

        DB::table('document_templates')->insert([
            [
                'type' => 'quote',
                'client_id' => null,
                'name' => 'Cotización predeterminada',
                'html_body' => (string) file_get_contents(resource_path('templates/defaults/quote.html')),
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'billing',
                'client_id' => null,
                'name' => 'Cuenta de cobro predeterminada',
                'html_body' => (string) file_get_contents(resource_path('templates/defaults/billing.html')),
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
};
