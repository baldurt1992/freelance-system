<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'type',
    'slug',
    'name',
])]
class FinanceCategory extends Model
{
    public function financeEntries(): HasMany
    {
        return $this->hasMany(FinanceEntry::class);
    }
}
