<?php

declare(strict_types=1);

namespace App\Application\Billing;

use Illuminate\Support\Facades\DB;

/**
 * Monotonic, gap-free billing document number generator per tenant.
 * Must be called inside a database transaction.
 */
final class BillingNumberGenerator
{
    public static function next(): string
    {
        $sequenceId = DB::table('billing_sequences')->insertGetId([]);

        return 'CC-' . str_pad((string) $sequenceId, 6, '0', STR_PAD_LEFT);
    }
}
