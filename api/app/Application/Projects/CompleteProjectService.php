<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Application\Billing\BillingNumberGenerator;
use App\Application\Billing\BillingPdfGenerator;
use App\Jobs\SendBillingDocumentEmail;
use App\Models\BillingDocument;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CompleteProjectService
{
    public function __construct(
        private readonly BillingPdfGenerator $pdfGenerator,
    ) {}

    /**
     * @return array{project: Project, billing_document: BillingDocument}
     */
    public function complete(Project $project): array
    {
        if ($project->status === 'completed') {
            $existing = $project->billingDocument;

            if ($existing === null) {
                throw new ConflictHttpException(
                    'El proyecto está completado pero no tiene cuenta de cobro asociada.',
                );
            }

            return [
                'project' => $project->fresh(['billingDocument']),
                'billing_document' => $existing,
            ];
        }

        if ($project->status !== 'active') {
            throw new ConflictHttpException(
                'Solo se pueden completar proyectos en estado activo.',
            );
        }

        return DB::transaction(function () use ($project) {
            $now = Carbon::now();
            $issuedAt = $now->copy();

            $project->update([
                'status' => 'completed',
                'completed_at' => $now->toDateString(),
            ]);

            $billingDocument = BillingDocument::query()->create([
                'project_id' => $project->id,
                'client_id' => $project->client_id,
                'number' => BillingNumberGenerator::next(),
                'status' => 'issued',
                'project_name' => $project->name,
                'client_name' => $project->client_name,
                'client_email' => $project->client_email,
                'client_tax_id' => $project->client_tax_id,
                'client_address' => $project->client_address,
                'currency' => $project->currency,
                'agreed_total_cents' => $project->agreed_total_cents,
                'paid_total_cents' => $project->paid_total_cents,
                'balance_due_cents' => $project->balance_due_cents,
                'issued_at' => $issuedAt,
            ]);

            $pdfPath = $this->pdfGenerator->store($billingDocument);
            $billingDocument->update(['pdf_path' => $pdfPath]);

            SendBillingDocumentEmail::dispatch($billingDocument->id)->afterCommit();

            Log::info('[Billing] issued', [
                'project_id' => $project->id,
                'billing_document_id' => $billingDocument->id,
                'number' => $billingDocument->number,
            ]);

            return [
                'project' => $project->fresh(['billingDocument']),
                'billing_document' => $billingDocument->fresh(),
            ];
        });
    }
}
