<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->text('notes')->nullable()->after('name');
        });

        $projects = DB::table('projects')
            ->whereNotNull('quote_id')
            ->orderBy('id')
            ->cursor();

        foreach ($projects as $project) {
            $quote = DB::table('quotes')
                ->select('notes')
                ->where('id', $project->quote_id)
                ->first();

            if ($quote?->notes === null) {
                continue;
            }

            DB::table('projects')
                ->where('id', $project->id)
                ->update(['notes' => $quote->notes]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('notes');
        });
    }
};
