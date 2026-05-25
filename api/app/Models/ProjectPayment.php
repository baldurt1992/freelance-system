<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id',
    'amount_cents',
    'kind',
    'paid_at',
])]
class ProjectPayment extends Model
{
    public const UPDATED_AT = null;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'paid_at' => 'date:Y-m-d',
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
