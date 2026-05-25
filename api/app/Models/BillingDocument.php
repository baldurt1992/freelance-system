<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id',
    'client_id',
    'number',
    'status',
    'project_name',
    'client_name',
    'client_email',
    'client_tax_id',
    'client_address',
    'currency',
    'agreed_total_cents',
    'paid_total_cents',
    'balance_due_cents',
    'pdf_path',
    'issued_at',
    'sent_at',
])]
class BillingDocument extends Model
{
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
