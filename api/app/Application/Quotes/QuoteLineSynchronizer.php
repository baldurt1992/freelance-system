<?php

declare(strict_types=1);

namespace App\Application\Quotes;

use App\Models\Quote;
use App\Models\QuoteLine;

/**
 * Syncs quote lines: removes existing and creates new ones.
 */
final class QuoteLineSynchronizer
{
    /**
     * @param  array<int, array<string, mixed>>  $calculatedLines
     */
    public function sync(Quote $quote, array $calculatedLines): void
    {
        $quote->lines()->delete();

        foreach ($calculatedLines as $lineData) {
            $lineData['quote_id'] = $quote->id;
            QuoteLine::query()->create($lineData);
        }
    }
}
