<?php

declare(strict_types=1);

namespace App\Application\Finances;

use App\Models\FinanceEntry;
use App\Models\ProjectPayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class FinanceEntryService
{
    public function list(int $perPage = 15, ?string $month = null, ?string $type = null): LengthAwarePaginator
    {
        $query = FinanceEntry::query()->orderByDesc('occurred_on')->orderByDesc('id');

        if ($month !== null && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            [$year, $mon] = explode('-', $month);
            $query->whereYear('occurred_on', (int) $year)->whereMonth('occurred_on', (int) $mon);
        }

        if ($type !== null && in_array($type, ['income', 'expense'], true)) {
            $query->where('type', $type);
        }

        return $query->paginate($perPage);
    }

    public function find(string|int $id): ?FinanceEntry
    {
        return FinanceEntry::query()->find($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createManualEntry(array $data): FinanceEntry
    {
        return FinanceEntry::query()->create([
            'type' => $data['type'],
            'amount_cents' => (int) $data['amount_cents'],
            'occurred_on' => $data['occurred_on'],
            'description' => $data['description'],
            'category' => $data['category'] ?? null,
            'source_type' => 'manual',
            'source_id' => null,
            'is_manual' => true,
        ]);
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
                'description' => 'Pago de proyecto',
                'category' => 'project_payment',
                'is_manual' => false,
            ],
        );

        return $entry;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateManualEntry(FinanceEntry $entry, array $data): FinanceEntry
    {
        if (! $entry->is_manual) {
            throw new ConflictHttpException('Las entradas automáticas no se pueden editar.');
        }

        $entry->update([
            'amount_cents' => isset($data['amount_cents']) ? (int) $data['amount_cents'] : $entry->amount_cents,
            'occurred_on' => $data['occurred_on'] ?? $entry->occurred_on?->toDateString(),
            'description' => $data['description'] ?? $entry->description,
            'category' => array_key_exists('category', $data) ? $data['category'] : $entry->category,
        ]);

        return $entry->fresh();
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
}
