<?php

declare(strict_types=1);

namespace App\Application\Quotes;

use App\Models\Quote;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class QuoteStatusService
{
    private const VALID_TRANSITIONS = [
        'draft' => ['sent'],
        'sent' => ['accepted', 'rejected'],
        'accepted' => [],
        'rejected' => [],
        'converted' => [],
    ];

    public function send(Quote $quote): Quote
    {
        $this->transition($quote, 'sent');
        $quote->sent_at = Carbon::now();
        $quote->save();

        return $quote->fresh();
    }

    public function accept(Quote $quote): Quote
    {
        $this->transition($quote, 'accepted');
        $quote->accepted_at = Carbon::now();
        $quote->save();

        return $quote->fresh();
    }

    public function reject(Quote $quote): Quote
    {
        $this->transition($quote, 'rejected');
        $quote->rejected_at = Carbon::now();
        $quote->save();

        return $quote->fresh();
    }

    private function transition(Quote $quote, string $to): void
    {
        $from = $quote->status;
        $allowed = self::VALID_TRANSITIONS[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new ConflictHttpException(
                "Transición inválida: {$from} -> {$to}",
            );
        }

        $quote->status = $to;
    }
}
