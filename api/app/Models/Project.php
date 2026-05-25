<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'client_id',
    'quote_id',
    'name',
    'notes',
    'type',
    'status',
    'quote_number',
    'client_name',
    'client_email',
    'client_tax_id',
    'client_address',
    'currency',
    'agreed_total_cents',
    'paid_total_cents',
    'balance_due_cents',
    'is_fully_paid',
    'started_at',
    'completed_at',
])]
class Project extends Model
{
    protected function casts(): array
    {
        return [
            'is_fully_paid' => 'boolean',
            'started_at' => 'date:Y-m-d',
            'completed_at' => 'date:Y-m-d',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ProjectPayment::class)->orderBy('paid_at')->orderBy('created_at');
    }

    public function billingDocument(): HasOne
    {
        return $this->hasOne(BillingDocument::class);
    }
}
