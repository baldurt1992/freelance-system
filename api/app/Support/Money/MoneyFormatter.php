<?php

declare(strict_types=1);

namespace App\Support\Money;

final class MoneyFormatter
{
    public static function format(int $cents, string $currency): string
    {
        return number_format($cents / 100, 2, '.', ',') . ' ' . $currency;
    }
}
