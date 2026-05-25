<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Application\Finances\FinanceEntryService;
use App\Models\Project;
use App\Models\ProjectPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class ProjectPaymentService
{
    public function __construct(
        private readonly FinanceEntryService $financeEntryService,
    ) {}

    /**
     * @return array{project: Project, payment: ProjectPayment}
     */
    public function registerPartialPayment(Project $project, int $amountCents, ?string $paidAt = null): array
    {
        if ($amountCents <= 0) {
            throw new UnprocessableEntityHttpException(
                'El monto del pago debe ser mayor a cero.',
            );
        }

        if ($amountCents > $project->balance_due_cents) {
            throw new UnprocessableEntityHttpException(
                'El monto del pago no puede exceder el saldo por cobrar.',
            );
        }

        $paidDate = $paidAt ?? Carbon::now()->toDateString();

        return DB::transaction(function () use ($project, $amountCents, $paidDate) {
            $payment = ProjectPayment::query()->create([
                'project_id' => $project->id,
                'amount_cents' => $amountCents,
                'kind' => 'partial',
                'paid_at' => $paidDate,
            ]);

            $this->financeEntryService->createFromProjectPayment($payment);

            $paidTotal = $project->paid_total_cents + $amountCents;
            $balance = $project->agreed_total_cents - $paidTotal;

            $project->update([
                'paid_total_cents' => $paidTotal,
                'balance_due_cents' => $balance,
                'is_fully_paid' => $balance === 0,
            ]);

            return [
                'project' => $project->fresh(),
                'payment' => $payment->fresh(),
            ];
        });
    }

    /**
     * @return array{project: Project, payment: ProjectPayment|null}
     */
    public function markProjectPaid(Project $project, ?string $paidAt = null): array
    {
        $alreadyFullyPaid = $project->is_fully_paid;
        $hasPayments = $project->payments()->exists();

        // Idempotent: already fully paid — return last payment if any, else null
        if ($alreadyFullyPaid) {
            return [
                'project' => $project,
                'payment' => $hasPayments ? $project->payments()->latest('created_at')->first() : null,
            ];
        }

        $amountToBook = $project->balance_due_cents;

        // balance already 0 but flag not set → fix invariant, no payment created
        if ($amountToBook <= 0) {
            $project->update([
                'is_fully_paid' => true,
            ]);

            return [
                'project' => $project->fresh(),
                'payment' => $hasPayments ? $project->payments()->latest('created_at')->first() : null,
            ];
        }

        $paidDate = $paidAt ?? Carbon::now()->toDateString();

        return DB::transaction(function () use ($project, $amountToBook, $paidDate) {
            $payment = ProjectPayment::query()->create([
                'project_id' => $project->id,
                'amount_cents' => $amountToBook,
                'kind' => 'closure',
                'paid_at' => $paidDate,
            ]);

            $this->financeEntryService->createFromProjectPayment($payment);

            $project->update([
                'paid_total_cents' => $project->agreed_total_cents,
                'balance_due_cents' => 0,
                'is_fully_paid' => true,
            ]);

            return [
                'project' => $project->fresh(),
                'payment' => $payment->fresh(),
            ];
        });
    }
}
