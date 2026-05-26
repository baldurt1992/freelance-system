<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Application\Projects\QuoteToProjectService;
use App\Application\Quotes\QuotePdfGenerator;
use App\Application\Quotes\QuoteService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Quote\StoreQuoteRequest;
use App\Http\Requests\Quote\UpdateQuoteRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class QuoteController extends Controller
{
    public function __construct(
        private readonly QuoteService $quoteService,
        private readonly QuotePdfGenerator $pdfGenerator,
        private readonly QuoteToProjectService $quoteToProjectService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $paginator = $this->quoteService->list(
            search: is_string($search) ? $search : null,
        );

        return $this->paginatedResponse(
            $paginator,
            QuoteResource::collection($paginator->items()),
        );
    }

    public function store(StoreQuoteRequest $request): JsonResponse
    {
        $quote = $this->quoteService->create($request->validated());

        return response()->json(new QuoteResource($quote), HttpResponse::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $quote = $this->findQuoteOrNotFoundResponse($id);
        if ($quote instanceof JsonResponse) {
            return $quote;
        }

        return response()->json(new QuoteResource($quote));
    }

    public function update(UpdateQuoteRequest $request, string $id): JsonResponse
    {
        $quote = $this->findQuoteOrNotFoundResponse($id);
        if ($quote instanceof JsonResponse) {
            return $quote;
        }

        $updated = $this->quoteService->update($quote, $request->validated());

        return response()->json(new QuoteResource($updated));
    }

    public function destroy(string $id): JsonResponse
    {
        $quote = $this->findQuoteOrNotFoundResponse($id);
        if ($quote instanceof JsonResponse) {
            return $quote;
        }

        $this->quoteService->delete($quote);

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function send(string $id): JsonResponse
    {
        $quote = $this->findQuoteOrNotFoundResponse($id);
        if ($quote instanceof JsonResponse) {
            return $quote;
        }

        $updated = $this->quoteService->send($quote);

        return response()->json($this->transitionResponse($updated));
    }

    public function accept(string $id): JsonResponse
    {
        $quote = $this->findQuoteOrNotFoundResponse($id);
        if ($quote instanceof JsonResponse) {
            return $quote;
        }

        $updated = $this->quoteService->accept($quote);

        return response()->json($this->transitionResponse($updated));
    }

    public function reject(string $id): JsonResponse
    {
        $quote = $this->findQuoteOrNotFoundResponse($id);
        if ($quote instanceof JsonResponse) {
            return $quote;
        }

        $updated = $this->quoteService->reject($quote);

        return response()->json($this->transitionResponse($updated));
    }

    public function pdf(string $id): Response|JsonResponse
    {
        $quote = $this->findQuoteOrNotFoundResponse($id);
        if ($quote instanceof JsonResponse) {
            return $quote;
        }

        $pdfContent = $this->pdfGenerator->generate($quote);

        return response($pdfContent, HttpResponse::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $quote->number . '.pdf"',
        ]);
    }

    public function convertToProject(string $id): JsonResponse
    {
        $quote = $this->findQuoteOrNotFoundResponse($id);
        if ($quote instanceof JsonResponse) {
            return $quote;
        }

        $project = $this->quoteToProjectService->convert($quote);

        return response()->json(new ProjectResource($project), HttpResponse::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     */
    private function transitionResponse(Quote $quote): array
    {
        return [
            'id' => $quote->id,
            'status' => $quote->status,
            'sent_at' => $quote->sent_at?->toIso8601String(),
            'accepted_at' => $quote->accepted_at?->toIso8601String(),
            'rejected_at' => $quote->rejected_at?->toIso8601String(),
        ];
    }

    private function findQuoteOrNotFoundResponse(string $id): Quote|JsonResponse
    {
        $quote = $this->quoteService->find($id);

        if ($quote === null) {
            return $this->notFoundResponse('Cotización no encontrada');
        }

        return $quote;
    }
}
