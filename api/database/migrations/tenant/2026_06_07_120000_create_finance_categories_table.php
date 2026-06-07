<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('slug');
            $table->string('name');
            $table->timestamps();

            $table->unique(['type', 'slug']);
            $table->index(['type', 'name']);
        });

        Schema::table('finance_entries', function (Blueprint $table) {
            $table->foreignId('finance_category_id')
                ->nullable()
                ->after('category')
                ->constrained('finance_categories')
                ->nullOnDelete();
        });

        $manualCategories = DB::table('finance_entries')
            ->select('type', 'category')
            ->where('is_manual', true)
            ->whereNotNull('category')
            ->distinct()
            ->get();

        foreach ($manualCategories as $row) {
            $category = trim((string) $row->category);
            $type = (string) $row->type;

            if ($category === '' || ! in_array($type, ['income', 'expense'], true)) {
                continue;
            }

            $slug = Str::of($category)
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->value();

            if ($slug === '') {
                continue;
            }

            $name = Str::of($category)
                ->replace(['_', '-'], ' ')
                ->title()
                ->value();

            $existingId = DB::table('finance_categories')
                ->where('type', $type)
                ->where('slug', $slug)
                ->value('id');

            $id = $existingId !== null
                ? (int) $existingId
                : (int) DB::table('finance_categories')->insertGetId([
                    'type' => $type,
                    'slug' => $slug,
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('finance_entries')
                ->where('is_manual', true)
                ->where('type', $type)
                ->where('category', $category)
                ->update(['finance_category_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('finance_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finance_category_id');
        });

        Schema::dropIfExists('finance_categories');
    }
};
