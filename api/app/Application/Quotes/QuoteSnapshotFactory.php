<?php

declare(strict_types=1);

namespace App\Application\Quotes;

use App\Models\Client;

/**
 * Pure helper: freezes client data into the quote snapshot fields.
 */
final class QuoteSnapshotFactory
{
    /**
     * @return array<string, mixed>
     */
    public static function fromClient(Client $client): array
    {
        return [
            'client_name' => $client->name,
            'client_email' => $client->email,
            'client_tax_id' => $client->tax_id,
            'client_address' => $client->address,
        ];
    }
}
