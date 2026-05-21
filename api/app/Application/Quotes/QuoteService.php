<?php

declare(strict_types=1);

namespace App\Application\Quotes;

use App\Models\Client;
use App\Models\Quote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class QuoteService
{
    public function __construct(
        private readonly QuoteTotalsCalculator $totalsCalculator,
        private readonly QuoteLineSynchronizer $lineSynchronizer,
        private readonly QuoteStatusService $statusService,
    ) {}

    public function list(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = Quote::query()
            ->with('lines')
            ->orderBy('created_at', 'desc');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function find(string|int $id): ?Quote
    {
        return Quote::query()->with('lines')->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Quote
    {
        $client = Client::query()->find($data['client_id']);

        if ($client === null) {
            throw new ModelNotFoundException('Cliente no encontrado');
        }

        $linesInput = $data['lines'];
        $taxEnabled = (bool) (tenant()->tax_enabled ?? false);
        $defaultTaxRate = (float) (tenant()->data['tax_rate'] ?? 0.0);
        $currency = (string) (tenant()->currency ?? 'COP');

        $totals = $this->totalsCalculator->calculate(
            $linesInput,
            $taxEnabled,
            $defaultTaxRate,
        );

        return DB::transaction(function () use ($client, $data, $currency, $totals) {
            $number = QuoteNumberGenerator::next();

            $quote = Quote::query()->create([
                'client_id' => $client->id,
                'number' => $number,
                'status' => 'draft',
                'title' => $data['title'] ?? null,
                'notes' => $data['notes'] ?? null,
                'currency' => $currency,
                'subtotal_cents' => $totals['subtotal_cents'],
                'tax_cents' => $totals['tax_cents'],
                'total_cents' => $totals['total_cents'],
                'tax_rate' => $totals['tax_rate'],
                'valid_until' => $data['valid_until'] ?? null,
                ...QuoteSnapshotFactory::fromClient($client),
            ]);

            $this->lineSynchronizer->sync($quote, $totals['lines']);

            return $quote->fresh()->load('lines');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Quote $quote, array $data): Quote
    {
        if ($quote->status !== 'draft') {
            throw new ConflictHttpException(
                'Solo se pueden editar cotizaciones en estado borrador.',
            );
        }

        $taxEnabled = (bool) (tenant()->tax_enabled ?? false);
        $defaultTaxRate = (float) (tenant()->data['tax_rate'] ?? 0.0);

        $updatePayload = [
            'title' => $data['title'] ?? $quote->title,
            'notes' => $data['notes'] ?? $quote->notes,
            'valid_until' => $data['valid_until'] ?? $quote->valid_until,
        ];

        if (isset($data['lines'])) {
            $totals = $this->totalsCalculator->calculate(
                $data['lines'],
                $taxEnabled,
                $defaultTaxRate,
            );

            return DB::transaction(function () use ($quote, $updatePayload, $totals) {
                $quote->update([
                    ...$updatePayload,
                    'subtotal_cents' => $totals['subtotal_cents'],
                    'tax_cents' => $totals['tax_cents'],
                    'total_cents' => $totals['total_cents'],
                    'tax_rate' => $totals['tax_rate'],
                ]);

                $this->lineSynchronizer->sync($quote, $totals['lines']);

                return $quote->fresh()->load('lines');
            });
        }

        $quote->update($updatePayload);

        return $quote->fresh()->load('lines');
    }

    public function delete(Quote $quote): void
    {
        if ($quote->status !== 'draft') {
            throw new ConflictHttpException(
                'Solo se pueden eliminar cotizaciones en estado borrador.',
            );
        }

        DB::transaction(function () use ($quote): void {
            $quote->lines()->delete();
            $quote->delete();
        });
    }

    public function send(Quote $quote): Quote
    {
        return $this->statusService->send($quote);
    }

    public function accept(Quote $quote): Quote
    {
        return $this->statusService->accept($quote);
    }

    public function reject(Quote $quote): Quote
    {
        return $this->statusService->reject($quote);
    }
}
