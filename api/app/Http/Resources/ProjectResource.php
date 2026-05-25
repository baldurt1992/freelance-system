<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'quote_id' => $this->quote_id,
            'name' => $this->name,
            'notes' => $this->notes,
            'type' => $this->type,
            'status' => $this->status,
            'quote_number' => $this->quote_number,
            'client_name' => $this->client_name,
            'client_email' => $this->client_email,
            'client_tax_id' => $this->client_tax_id,
            'client_address' => $this->client_address,
            'currency' => $this->currency,
            'agreed_total_cents' => $this->agreed_total_cents,
            'paid_total_cents' => $this->paid_total_cents,
            'balance_due_cents' => $this->balance_due_cents,
            'is_fully_paid' => $this->is_fully_paid,
            'started_at' => $this->started_at?->toDateString(),
            'completed_at' => $this->completed_at?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
