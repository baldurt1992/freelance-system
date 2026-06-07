<?php

declare(strict_types=1);

namespace App\Application\Finances;

use App\Models\FinanceCategory;
use App\Models\FinanceEntry;
use App\Models\ProjectPayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class FinanceEntryService
{
    public function list(int $perPage = 15, ?string $month = null, ?string $type = null, ?string $search = null): LengthAwarePaginator
    {
        $query = FinanceEntry::query()
            ->with('financeCategory')
            ->orderByDesc('occurred_on')
            ->orderByDesc('id');

        if ($month !== null && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            [$year, $mon] = explode('-', $month);
            $query->whereYear('occurred_on', (int) $year)->whereMonth('occurred_on', (int) $mon);
        }

        if ($type !== null && in_array($type, ['income', 'expense'], true)) {
            $query->where('type', $type);
        }

        if ($search !== null && trim($search) !== '') {
            $needle = '%' . trim($search) . '%';

            $query->where(function ($nested) use ($needle): void {
                $nested
                    ->where('name', 'like', $needle)
                    ->orWhere('description', 'like', $needle)
                    ->orWhere('category', 'like', $needle);
            });
        }

        return $query->paginate($perPage);
    }

    public function find(string|int $id): ?FinanceEntry
    {
        return FinanceEntry::query()->with('financeCategory')->find($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createManualEntry(array $data): FinanceEntry
    {
        $category = $this->resolveManualCategory(
            $data['type'],
            isset($data['category_id']) ? (int) $data['category_id'] : null,
        );

        return FinanceEntry::query()->create([
            'type' => $data['type'],
            'amount_cents' => (int) $data['amount_cents'],
            'occurred_on' => $data['occurred_on'],
            'name' => $data['name'],
            'description' => $data['description'],
            'category' => $category?->slug,
            'finance_category_id' => $category?->id,
            'source_type' => 'manual',
            'source_id' => null,
            'is_manual' => true,
        ])->load('financeCategory');
    }

    public function createFromProjectPayment(ProjectPayment $payment): FinanceEntry
    {
        /** @var FinanceEntry $entry */
        $entry = FinanceEntry::query()->firstOrCreate(
            [
                'source_type' => 'project_payment',
                'source_id' => $payment->id,
            ],
            [
                'type' => 'income',
                'amount_cents' => (int) $payment->amount_cents,
                'occurred_on' => $payment->paid_at?->toDateString(),
                'name' => 'Pago de proyecto',
                'description' => 'Pago de proyecto',
                'category' => 'project_payment',
                'finance_category_id' => null,
                'is_manual' => false,
            ],
        );

        return $entry->load('financeCategory');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateManualEntry(FinanceEntry $entry, array $data): FinanceEntry
    {
        if (! $entry->is_manual) {
            throw new ConflictHttpException('Las entradas automáticas no se pueden editar.');
        }

        $nextType = $data['type'] ?? $entry->type;
        $category = array_key_exists('category_id', $data)
            ? $this->resolveManualCategory($nextType, $data['category_id'] !== null ? (int) $data['category_id'] : null)
            : $entry->financeCategory;

        $entry->update([
            'type' => $nextType,
            'amount_cents' => isset($data['amount_cents']) ? (int) $data['amount_cents'] : $entry->amount_cents,
            'occurred_on' => $data['occurred_on'] ?? $entry->occurred_on?->toDateString(),
            'name' => $data['name'] ?? $entry->name,
            'description' => $data['description'] ?? $entry->description,
            'category' => array_key_exists('category_id', $data) ? $category?->slug : $entry->category,
            'finance_category_id' => array_key_exists('category_id', $data) ? $category?->id : $entry->finance_category_id,
        ]);

        return $entry->fresh()->load('financeCategory');
    }

    public function deleteManualEntry(FinanceEntry $entry): void
    {
        if (! $entry->is_manual) {
            throw new ConflictHttpException('Las entradas automáticas no se pueden eliminar.');
        }

        $entry->delete();
    }

    /**
     * @return array{
     *   month: string,
     *   total_income_cents: int,
     *   total_expense_cents: int,
     *   net_cents: int,
     *   label: string
     * }
     */
    public function summaryForMonth(string $month): array
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            throw new ModelNotFoundException('Mes inválido');
        }

        [$year, $mon] = explode('-', $month);

        $totals = FinanceEntry::query()
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount_cents ELSE 0 END), 0) as income_total,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount_cents ELSE 0 END), 0) as expense_total
            ")
            ->whereYear('occurred_on', (int) $year)
            ->whereMonth('occurred_on', (int) $mon)
            ->first();

        $income = (int) ($totals?->income_total ?? 0);
        $expense = (int) ($totals?->expense_total ?? 0);
        $net = $income - $expense;

        $label = $net > 0 ? 'surplus' : ($net < 0 ? 'shortfall' : 'balanced');

        return [
            'month' => $month,
            'total_income_cents' => $income,
            'total_expense_cents' => $expense,
            'net_cents' => $net,
            'label' => $label,
        ];
    }

    private function resolveManualCategory(string $type, ?int $categoryId): ?FinanceCategory
    {
        if ($categoryId === null) {
            return null;
        }

        /** @var FinanceCategory|null $category */
        $category = FinanceCategory::query()->find($categoryId);

        if ($category === null || $category->type !== $type) {
            throw new ConflictHttpException('La categoría no corresponde al tipo del movimiento.');
        }

        return $category;
    }
}
