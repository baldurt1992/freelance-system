<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\Client;
use App\Models\Quote;

/**
 * Pure helper: builds persistible project snapshot fields from Client or Quote.
 */
final class ProjectSnapshotFactory
{
    /**
     * @return array<string, mixed>
     */
    public static function clientFieldsFromClient(Client $client): array
    {
        return [
            'client_name' => $client->name,
            'client_email' => $client->email,
            'client_tax_id' => $client->tax_id,
            'client_address' => $client->address,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function clientFieldsFromQuote(Quote $quote): array
    {
        return [
            'client_name' => $quote->client_name,
            'client_email' => $quote->client_email,
            'client_tax_id' => $quote->client_tax_id,
            'client_address' => $quote->client_address,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function paymentDefaults(int $agreedTotalCents, string $currency): array
    {
        return [
            'currency' => $currency,
            'agreed_total_cents' => $agreedTotalCents,
            'paid_total_cents' => 0,
            'balance_due_cents' => $agreedTotalCents,
            'is_fully_paid' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function forManualCreate(Client $client, array $data, string $currency): array
    {
        $agreedTotalCents = (int) $data['agreed_total_cents'];

        return array_merge(
            [
                'client_id' => $client->id,
                'name' => $data['name'],
                'notes' => $data['notes'] ?? null,
                'type' => $data['type'] ?? 'freelance',
                'status' => 'active',
                'started_at' => $data['started_at'] ?? null,
            ],
            self::clientFieldsFromClient($client),
            self::paymentDefaults($agreedTotalCents, $currency),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function forQuoteConversion(Quote $quote, string $currency): array
    {
        return array_merge(
            [
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'name' => $quote->title ?? "Proyecto {$quote->number}",
                'notes' => $quote->notes,
                'type' => 'freelance',
                'status' => 'active',
                'quote_number' => $quote->number,
            ],
            self::clientFieldsFromQuote($quote),
            self::paymentDefaults((int) $quote->total_cents, $currency),
        );
    }
}
