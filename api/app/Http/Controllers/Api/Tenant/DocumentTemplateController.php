<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Application\Documents\DocumentTemplateService;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentTemplate\PreviewDocumentTemplateRequest;
use App\Http\Requests\DocumentTemplate\UpdateDocumentTemplateRequest;
use App\Http\Resources\DocumentTemplateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class DocumentTemplateController extends Controller
{
    public function __construct(
        private readonly DocumentTemplateService $templateService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $templates = $this->templateService->list(is_string($type) ? $type : null);

        return response()->json([
            'data' => DocumentTemplateResource::collection($templates),
        ]);
    }

    public function update(UpdateDocumentTemplateRequest $request, string $id): JsonResponse
    {
        $template = $this->templateService->find((int) $id);

        if ($template === null) {
            return response()->json(['message' => 'Plantilla no encontrada'], HttpResponse::HTTP_NOT_FOUND);
        }

        $updated = $this->templateService->update($template, $request->validated());

        return response()->json(new DocumentTemplateResource($updated));
    }

    public function preview(PreviewDocumentTemplateRequest $request): Response|JsonResponse
    {
        try {
            $pdfContent = $this->templateService->preview($request->validated());
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], HttpResponse::HTTP_NOT_FOUND);
        }

        return response($pdfContent, HttpResponse::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
        ]);
    }
}
