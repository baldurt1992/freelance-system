<?php

declare(strict_types=1);

namespace App\Support\Money;

/**
 * Single source of truth for monetary line calculations (integer cents).
 * VAT is optional; when disabled, tax_rate should be 0.
 */
final class MoneyMath
{
    public const MODE_NET_FIRST = 'net_first';

    public const MODE_GROSS_FIRST = 'gross_first';

    /**
     * @return array{
     *     line_net_cents: int,
     *     tax_cents: int,
     *     line_gross_cents: int,
     *     discounted_net_cents: int
     * }
     */
    public static function computeLineTotals(
        float|int $quantity,
        int $unitAmountCents,
        float $taxRatePercent,
        int $discountCents = 0,
        string $priceMode = self::MODE_NET_FIRST,
        bool $taxEnabled = true,
    ): array {
        if (! $taxEnabled) {
            $taxRatePercent = 0.0;
        }

        if ($priceMode === self::MODE_GROSS_FIRST) {
            return self::computeFromGross($quantity, $unitAmountCents, $taxRatePercent, $discountCents);
        }

        return self::computeFromNet($quantity, $unitAmountCents, $taxRatePercent, $discountCents);
    }

    /**
     * @return array{
     *     line_net_cents: int,
     *     tax_cents: int,
     *     line_gross_cents: int,
     *     discounted_net_cents: int
     * }
     */
    public static function computeFromNet(
        float|int $quantity,
        int $unitAmountCents,
        float $taxRatePercent,
        int $discountCents = 0,
    ): array {
        $lineNetCents = (int) round((float) $quantity * $unitAmountCents);
        $discountedNet = max(0, $lineNetCents - $discountCents);
        $taxCents = (int) round($discountedNet * $taxRatePercent / 100, 0, PHP_ROUND_HALF_UP);
        $lineGrossCents = $discountedNet + $taxCents;

        return [
            'line_net_cents' => $lineNetCents,
            'discounted_net_cents' => $discountedNet,
            'tax_cents' => $taxCents,
            'line_gross_cents' => $lineGrossCents,
        ];
    }

    /**
     * @return array{
     *     line_net_cents: int,
     *     tax_cents: int,
     *     line_gross_cents: int,
     *     discounted_net_cents: int
     * }
     */
    public static function computeFromGross(
        float|int $quantity,
        int $unitAmountInclTaxCents,
        float $taxRatePercent,
        int $discountCents = 0,
    ): array {
        $lineGrossCents = (int) round((float) $quantity * $unitAmountInclTaxCents);
        $lineGrossAfterDiscount = max(0, $lineGrossCents - $discountCents);

        if ($taxRatePercent <= 0) {
            return [
                'line_net_cents' => $lineGrossAfterDiscount,
                'discounted_net_cents' => $lineGrossAfterDiscount,
                'tax_cents' => 0,
                'line_gross_cents' => $lineGrossAfterDiscount,
            ];
        }

        $taxCents = (int) round(
            $lineGrossAfterDiscount * $taxRatePercent / (100 + $taxRatePercent),
            0,
            PHP_ROUND_HALF_UP
        );
        $lineNetCents = $lineGrossAfterDiscount - $taxCents;

        return [
            'line_net_cents' => $lineNetCents,
            'discounted_net_cents' => $lineNetCents,
            'tax_cents' => $taxCents,
            'line_gross_cents' => $lineGrossAfterDiscount,
        ];
    }
}
