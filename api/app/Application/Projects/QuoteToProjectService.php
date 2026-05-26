<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\Project;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class QuoteToProjectService
{
    public function convert(Quote $quote): Project
    {
        if ($quote->status !== 'accepted') {
            throw new ConflictHttpException(
                'Solo se pueden convertir cotizaciones en estado aceptado.',
            );
        }

        if (Project::query()->where('quote_id', $quote->id)->exists()) {
            throw new ConflictHttpException(
                'Esta cotización ya fue convertida a proyecto.',
            );
        }

        $currency = (string) (tenant()->currency ?? 'COP');

        return DB::transaction(function () use ($quote, $currency) {
            $project = Project::query()->create(
                ProjectSnapshotFactory::forQuoteConversion($quote, $currency),
            );

            $quote->status = 'converted';
            $quote->save();

            return $project->fresh();
        });
    }
}
