<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Application\Billing\BillingPdfGenerator;
use App\Http\Controllers\Controller;
use App\Http\Resources\BillingDocumentResource;
use App\Models\BillingDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class BillingDocumentController extends Controller
{
    public function __construct(
        private readonly BillingPdfGenerator $pdfGenerator,
    ) {}

    public function pdf(string $id): Response|JsonResponse
    {
        $document = BillingDocument::query()->find($id);

        if ($document === null) {
            return response()->json(['message' => 'Cuenta de cobro no encontrada'], HttpResponse::HTTP_NOT_FOUND);
        }

        if ($document->pdf_path !== null && Storage::disk('local')->exists($document->pdf_path)) {
            $pdfContent = Storage::disk('local')->get($document->pdf_path);
        } else {
            $pdfContent = $this->pdfGenerator->generate($document);
        }

        return response($pdfContent, HttpResponse::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $document->number . '.pdf"',
        ]);
    }

    public function markSent(string $id): JsonResponse
    {
        $document = BillingDocument::query()->find($id);

        if ($document === null) {
            return response()->json(['message' => 'Cuenta de cobro no encontrada'], HttpResponse::HTTP_NOT_FOUND);
        }

        if ($document->status !== 'sent') {
            $document->update([
                'status' => 'sent',
                'sent_at' => $document->sent_at ?? now(),
            ]);
        }

        return response()->json(new BillingDocumentResource($document->fresh()));
    }
}
