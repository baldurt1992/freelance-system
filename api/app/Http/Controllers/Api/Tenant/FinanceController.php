<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Application\Finances\FinanceEntryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFinanceEntryRequest;
use App\Http\Requests\Finance\UpdateFinanceEntryRequest;
use App\Http\Resources\FinanceEntryResource;
use App\Models\FinanceEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class FinanceController extends Controller
{
    public function __construct(
        private readonly FinanceEntryService $financeEntryService,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $month = $request->query('month');
        $monthValue = is_string($month) && $month !== '' ? $month : now()->format('Y-m');

        return response()->json($this->financeEntryService->summaryForMonth($monthValue));
    }

    public function index(Request $request): JsonResponse
    {
        $month = $request->query('month');
        $type = $request->query('type');

        $paginator = $this->financeEntryService->list(
            month: is_string($month) ? $month : null,
            type: is_string($type) ? $type : null,
        );

        return $this->paginatedResponse(
            $paginator,
            FinanceEntryResource::collection($paginator->items()),
        );
    }

    public function store(StoreFinanceEntryRequest $request): JsonResponse
    {
        $entry = $this->financeEntryService->createManualEntry($request->validated());

        return response()->json(new FinanceEntryResource($entry), HttpResponse::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $entry = $this->findEntryOrNotFoundResponse($id);
        if ($entry instanceof JsonResponse) {
            return $entry;
        }

        return response()->json(new FinanceEntryResource($entry));
    }

    public function update(UpdateFinanceEntryRequest $request, string $id): JsonResponse
    {
        $entry = $this->findEntryOrNotFoundResponse($id);
        if ($entry instanceof JsonResponse) {
            return $entry;
        }

        $updated = $this->financeEntryService->updateManualEntry($entry, $request->validated());

        return response()->json(new FinanceEntryResource($updated));
    }

    public function destroy(string $id): JsonResponse
    {
        $entry = $this->findEntryOrNotFoundResponse($id);
        if ($entry instanceof JsonResponse) {
            return $entry;
        }

        $this->financeEntryService->deleteManualEntry($entry);

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    private function findEntryOrNotFoundResponse(string $id): FinanceEntry|JsonResponse
    {
        $entry = $this->financeEntryService->find($id);

        if ($entry === null) {
            return $this->notFoundResponse('Movimiento no encontrado');
        }

        return $entry;
    }
}
