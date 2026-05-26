<?php

declare(strict_types=1);

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Landlord tenant record. Workspace settings live in the JSON `data` column
 * (tax_enabled, currency) via Stancl's VirtualColumn API.
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'created_at',
            'updated_at',
        ];
    }

    public function getTaxEnabledAttribute(): bool
    {
        return (bool) ($this->getAttributeFromArray('tax_enabled') ?? false);
    }

    public function getCurrencyAttribute(): string
    {
        return (string) ($this->getAttributeFromArray('currency') ?? 'COP');
    }

    public function getTaxRateAttribute(): float
    {
        return (float) ($this->getAttributeFromArray('tax_rate') ?? 19.0);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function mergeSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }
}
