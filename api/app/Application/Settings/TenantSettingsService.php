<?php

declare(strict_types=1);

namespace App\Application\Settings;

use App\Application\Quotes\DraftQuoteTaxRecalculator;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

final class TenantSettingsService
{
    public function __construct(
        private readonly DraftQuoteTaxRecalculator $draftQuoteTaxRecalculator,
    ) {}

    /**
     * @param  array{tax_enabled: bool}  $data
     */
    public function update(array $data): Tenant
    {
        /** @var Tenant $tenant */
        $tenant = tenant();
        $previousTaxEnabled = $tenant->tax_enabled;
        $newTaxEnabled = $data['tax_enabled'];

        $settings = ['tax_enabled' => $newTaxEnabled];

        if ($newTaxEnabled && blank($tenant->getAttributeValue('tax_rate'))) {
            $settings['tax_rate'] = 19.0;
        }

        $tenant->mergeSettings($settings);
        $tenant->save();

        if ($previousTaxEnabled !== $newTaxEnabled) {
            $count = $this->draftQuoteTaxRecalculator->recalculateAll();

            Log::info('[Settings] tax_enabled changed', [
                'tenant_id' => $tenant->id,
                'tax_enabled' => $newTaxEnabled,
                'draft_quotes_recalculated' => $count,
            ]);
        }

        return $tenant->fresh();
    }
}
