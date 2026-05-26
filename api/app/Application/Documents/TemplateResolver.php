<?php

declare(strict_types=1);

namespace App\Application\Documents;

use App\Models\DocumentTemplate;
use RuntimeException;

final class TemplateResolver
{
    public function resolve(string $type, ?int $clientId = null): DocumentTemplate
    {
        if ($clientId !== null) {
            $clientTemplate = DocumentTemplate::query()
                ->where('type', $type)
                ->where('client_id', $clientId)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();

            if ($clientTemplate !== null) {
                return $clientTemplate;
            }
        }

        $defaultTemplate = DocumentTemplate::query()
            ->where('type', $type)
            ->whereNull('client_id')
            ->where('is_default', true)
            ->first();

        if ($defaultTemplate !== null) {
            return $defaultTemplate;
        }

        $fallback = DocumentTemplate::query()
            ->where('type', $type)
            ->whereNull('client_id')
            ->orderBy('id')
            ->first();

        if ($fallback !== null) {
            return $fallback;
        }

        throw new RuntimeException("No document template found for type [{$type}].");
    }
}
