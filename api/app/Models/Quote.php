<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_id',
    'number',
    'status',
    'title',
    'notes',
    'client_name',
    'client_email',
    'client_tax_id',
    'client_address',
    'currency',
    'subtotal_cents',
    'tax_cents',
    'total_cents',
    'tax_rate',
    'valid_until',
    'sent_at',
    'accepted_at',
    'rejected_at',
])]
class Quote extends Model
{
    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'valid_until' => 'date:Y-m-d',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class)->orderBy('sort_order');
    }
}
