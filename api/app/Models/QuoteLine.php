<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quote_id',
    'description',
    'quantity',
    'unit_amount_cents',
    'tax_rate',
    'tax_cents',
    'line_total_cents',
    'sort_order',
])]
class QuoteLine extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'tax_rate' => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
