<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quote */
final class QuoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'number' => $this->number,
            'status' => $this->status,
            'title' => $this->title,
            'notes' => $this->notes,
            'client_name' => $this->client_name,
            'client_email' => $this->client_email,
            'client_tax_id' => $this->client_tax_id,
            'client_address' => $this->client_address,
            'currency' => $this->currency,
            'subtotal_cents' => $this->subtotal_cents,
            'tax_cents' => $this->tax_cents,
            'total_cents' => $this->total_cents,
            'tax_rate' => (float) $this->tax_rate,
            'valid_until' => $this->valid_until?->toDateString(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'lines' => $this->whenLoaded('lines', function () {
                return $this->lines->map(function ($line) {
                    return [
                        'id' => $line->id,
                        'quote_id' => $line->quote_id,
                        'description' => $line->description,
                        'quantity' => (float) $line->quantity,
                        'unit_amount_cents' => $line->unit_amount_cents,
                        'tax_rate' => (float) $line->tax_rate,
                        'tax_cents' => $line->tax_cents,
                        'line_total_cents' => $line->line_total_cents,
                        'sort_order' => $line->sort_order,
                    ];
                });
            }),
        ];
    }
}
