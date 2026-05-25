<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProjectPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'amount_cents' => $this->amount_cents,
            'kind' => $this->kind,
            'paid_at' => $this->paid_at?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
