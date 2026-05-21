<?php

declare(strict_types=1);

namespace App\Application\Quotes;

use App\Models\Quote;
use App\Support\Money\MoneyMath;
use InvalidArgumentException;

/**
 * Calculates line and aggregate totals using MoneyMath only.
 * Backend is the single source of truth for monetary values.
 */
final class QuoteTotalsCalculator
{
    /**
     * @param  array<int, array<string, mixed>>  $lineInputs
     * @return array{
     *     lines: array<int, array<string, mixed>>,
     *     subtotal_cents: int,
     *     tax_cents: int,
     *     total_cents: int,
     *     tax_rate: float,
     * }
     */
    public function calculate(array $lineInputs, bool $taxEnabled, float $defaultTaxRate = 0.0): array
    {
        if (! $taxEnabled) {
            $defaultTaxRate = 0.0;
        }

        $calculatedLines = [];
        $subtotalCents = 0;
        $taxCents = 0;
        $totalCents = 0;

        foreach ($lineInputs as $input) {
            $quantity = (float) $input['quantity'];
            $unitAmountCents = (int) $input['unit_amount_cents'];
            $effectiveTaxRate = $taxEnabled ? $defaultTaxRate : 0.0;

            $lineTotals = MoneyMath::computeLineTotals(
                quantity: $quantity,
                unitAmountCents: $unitAmountCents,
                taxRatePercent: $effectiveTaxRate,
                taxEnabled: $taxEnabled,
            );

            $calculatedLines[] = [
                'description' => (string) $input['description'],
                'quantity' => $quantity,
                'unit_amount_cents' => $unitAmountCents,
                'tax_rate' => $effectiveTaxRate,
                'tax_cents' => $lineTotals['tax_cents'],
                'line_total_cents' => $lineTotals['line_gross_cents'],
                'sort_order' => (int) $input['sort_order'],
            ];

            $subtotalCents += $lineTotals['line_net_cents'];
            $taxCents += $lineTotals['tax_cents'];
            $totalCents += $lineTotals['line_gross_cents'];
        }

        return [
            'lines' => $calculatedLines,
            'subtotal_cents' => $subtotalCents,
            'tax_cents' => $taxCents,
            'total_cents' => $totalCents,
            'tax_rate' => $defaultTaxRate,
        ];
    }
}
