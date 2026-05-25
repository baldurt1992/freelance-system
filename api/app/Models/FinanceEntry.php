<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'type',
    'amount_cents',
    'occurred_on',
    'description',
    'category',
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
}
