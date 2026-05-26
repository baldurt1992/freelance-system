<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\BillingDocumentMail;
use App\Models\BillingDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía la cuenta de cobro por correo y marca el documento como `sent`.
 *
 * Tenant-aware vía Stancl {@see \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper}:
 * el worker inicializa tenancy desde `tenant_id` en el payload de la cola.
 * Este job solo persiste el ID del documento; no depende de estado efímero del request HTTP.
 */
final class SendBillingDocumentEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $billingDocumentId,
    ) {}

    public function handle(): void
    {
        $document = BillingDocument::query()->find($this->billingDocumentId);

        if ($document === null) {
            Log::warning('[Billing] email skipped — document not found', [
                'billing_document_id' => $this->billingDocumentId,
                'tenancy_initialized' => tenancy()->initialized,
                'tenant_id' => tenant()?->getTenantKey(),
            ]);

            return;
        }

        if ($document->status === 'sent') {
            Log::info('[Billing] email skipped — already sent', [
                'billing_document_id' => $document->id,
            ]);

            return;
        }

        $recipient = $document->client_email;

        if ($recipient === null || $recipient === '') {
            Log::warning('[Billing] email skipped — client has no email', [
                'billing_document_id' => $document->id,
                'project_id' => $document->project_id,
            ]);

            return;
        }

        Mail::to($recipient)->send(new BillingDocumentMail($document));

        $document->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        Log::info('[Billing] sent', [
            'billing_document_id' => $document->id,
            'project_id' => $document->project_id,
            'recipient' => $recipient,
        ]);
    }
}
