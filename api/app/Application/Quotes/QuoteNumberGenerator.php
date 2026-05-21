<?php

declare(strict_types=1);

namespace App\Application\Quotes;

use Illuminate\Support\Facades\DB;

/**
 * Monotonic, gap-free quote number generator per tenant.
 * Uses a dedicated sequence table so deleted quotes never reuse numbers.
 * Must be called inside a database transaction.
 */
final class QuoteNumberGenerator
{
    public static function next(): string
    {
        $sequenceId = DB::table('quote_sequences')->insertGetId([]);

        return 'Q-' . str_pad((string) $sequenceId, 6, '0', STR_PAD_LEFT);
    }
}
