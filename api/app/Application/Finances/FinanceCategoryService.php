<?php

declare(strict_types=1);

namespace App\Application\Finances;

use App\Models\FinanceCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class FinanceCategoryService
{
    /**
     * @return Collection<int, FinanceCategory>
     */
    public function list(?string $type = null): Collection
    {
        $query = FinanceCategory::query()
            ->orderBy('type')
            ->orderBy('name');

        if ($type !== null && in_array($type, ['income', 'expense'], true)) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    public function find(int $id): ?FinanceCategory
    {
        return FinanceCategory::query()->find($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): FinanceCategory
    {
        $type = (string) $data['type'];
        $name = trim((string) $data['name']);
        $slug = $this->slugify($name);

        $this->ensureSlugUnique($type, $slug);

        /** @var FinanceCategory $category */
        $category = FinanceCategory::query()->create([
            'type' => $type,
            'slug' => $slug,
            'name' => $name,
        ]);

        return $category;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(FinanceCategory $category, array $data): FinanceCategory
    {
        $name = trim((string) $data['name']);
        $slug = $this->slugify($name);

        $this->ensureSlugUnique($category->type, $slug, $category->id);

        $category->update([
            'name' => $name,
            'slug' => $slug,
        ]);

        $category->financeEntries()->update([
            'category' => $slug,
        ]);

        return $category->fresh();
    }

    public function delete(FinanceCategory $category): void
    {
        $category->delete();
    }

    private function ensureSlugUnique(string $type, string $slug, ?int $ignoreId = null): void
    {
        $query = FinanceCategory::query()
            ->where('type', $type)
            ->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw new ConflictHttpException('Ya existe una categoría con ese nombre para este tipo.');
        }
    }

    private function slugify(string $value): string
    {
        $slug = Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        if ($slug === '') {
            throw new ConflictHttpException('La categoría debe contener caracteres válidos.');
        }

        return $slug;
    }
}
