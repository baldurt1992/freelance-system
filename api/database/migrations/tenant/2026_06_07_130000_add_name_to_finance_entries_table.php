<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_entries', function (Blueprint $table) {
            $table->string('name')->nullable()->after('occurred_on');
        });

        DB::table('finance_entries')
            ->whereNull('name')
            ->update([
                'name' => DB::raw("COALESCE(NULLIF(description, ''), NULLIF(category, ''), 'Movimiento financiero')"),
            ]);

        Schema::table('finance_entries', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('finance_entries', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
