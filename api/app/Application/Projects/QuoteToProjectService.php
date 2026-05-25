<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Application\Quotes\QuoteSnapshotFactory;
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
            $project = Project::query()->create([
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'name' => $quote->title ?? "Proyecto {$quote->number}",
                'type' => 'freelance',
                'status' => 'active',
                'quote_number' => $quote->number,
                'client_name' => $quote->client_name,
                'client_email' => $quote->client_email,
                'client_tax_id' => $quote->client_tax_id,
                'client_address' => $quote->client_address,
                'currency' => $currency,
                'agreed_total_cents' => $quote->total_cents,
                'paid_total_cents' => 0,
                'balance_due_cents' => $quote->total_cents,
                'is_fully_paid' => false,
            ]);

            $quote->status = 'converted';
            $quote->save();

            return $project->fresh();
        });
    }
}
