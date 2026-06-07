<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FinanceEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount_cents' => $this->amount_cents,
            'occurred_on' => $this->occurred_on?->toDateString(),
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'category_id' => $this->finance_category_id,
            'category_name' => $this->financeCategory?->name,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'is_manual' => $this->is_manual,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
