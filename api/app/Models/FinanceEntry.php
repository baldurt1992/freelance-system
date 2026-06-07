<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'type',
    'amount_cents',
    'occurred_on',
    'name',
    'description',
    'category',
    'finance_category_id',
    'source_type',
    'source_id',
    'is_manual',
])]
class FinanceEntry extends Model
{
    protected function casts(): array
    {
        return [
            'is_manual' => 'boolean',
            'occurred_on' => 'date:Y-m-d',
        ];
    }

    public function financeCategory(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class);
    }
}
