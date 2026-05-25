<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BillingDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BillingDocument */
final class BillingDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'client_id' => $this->client_id,
            'number' => $this->number,
            'status' => $this->status,
            'project_name' => $this->project_name,
            'client_name' => $this->client_name,
            'client_email' => $this->client_email,
            'client_tax_id' => $this->client_tax_id,
            'client_address' => $this->client_address,
            'currency' => $this->currency,
            'agreed_total_cents' => $this->agreed_total_cents,
            'paid_total_cents' => $this->paid_total_cents,
            'balance_due_cents' => $this->balance_due_cents,
            'pdf_path' => $this->pdf_path,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
