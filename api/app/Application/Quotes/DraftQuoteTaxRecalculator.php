<?php

declare(strict_types=1);

namespace App\Application\Quotes;

use App\Models\Quote;
use Illuminate\Support\Facades\DB;

final class DraftQuoteTaxRecalculator
{
    public function __construct(
        private readonly QuoteTotalsCalculator $totalsCalculator,
        private readonly QuoteLineSynchronizer $lineSynchronizer,
    ) {}

    public function recalculateAll(): int
    {
        $quotes = Quote::query()
            ->where('status', 'draft')
            ->with('lines')
            ->get();

        $taxEnabled = (bool) (tenant()->tax_enabled ?? false);
        $defaultTaxRate = (float) (tenant()->tax_rate ?? 19.0);
        $recalculated = 0;

        foreach ($quotes as $quote) {
            DB::transaction(function () use ($quote, $taxEnabled, $defaultTaxRate, &$recalculated): void {
                $lineInputs = $quote->lines->map(static fn ($line) => [
                    'description' => $line->description,
                    'quantity' => (float) $line->quantity,
                    'unit_amount_cents' => $line->unit_amount_cents,
                    'sort_order' => $line->sort_order,
                ])->all();

                if ($lineInputs === []) {
                    return;
                }

                $totals = $this->totalsCalculator->calculate(
                    $lineInputs,
                    $taxEnabled,
                    $defaultTaxRate,
                );

                $quote->update([
                    'subtotal_cents' => $totals['subtotal_cents'],
                    'tax_cents' => $totals['tax_cents'],
                    'total_cents' => $totals['total_cents'],
                    'tax_rate' => $totals['tax_rate'],
                ]);

                $this->lineSynchronizer->sync($quote, $totals['lines']);
                $recalculated++;
            });
        }

        return $recalculated;
    }
}
