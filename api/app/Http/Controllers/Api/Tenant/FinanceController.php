<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Application\Finances\FinanceEntryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFinanceEntryRequest;
use App\Http\Requests\Finance\UpdateFinanceEntryRequest;
use App\Http\Resources\FinanceEntryResource;
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

        return response()->json([
            'data' => FinanceEntryResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreFinanceEntryRequest $request): JsonResponse
    {
        $entry = $this->financeEntryService->createManualEntry($request->validated());

        return response()->json(new FinanceEntryResource($entry), HttpResponse::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $entry = $this->financeEntryService->find($id);

        if ($entry === null) {
            return response()->json(['message' => 'Movimiento no encontrado'], HttpResponse::HTTP_NOT_FOUND);
        }

        return response()->json(new FinanceEntryResource($entry));
    }

    public function update(UpdateFinanceEntryRequest $request, string $id): JsonResponse
    {
        $entry = $this->financeEntryService->find($id);

        if ($entry === null) {
            return response()->json(['message' => 'Movimiento no encontrado'], HttpResponse::HTTP_NOT_FOUND);
        }

        $updated = $this->financeEntryService->updateManualEntry($entry, $request->validated());

        return response()->json(new FinanceEntryResource($updated));
    }

    public function destroy(string $id): JsonResponse
    {
        $entry = $this->financeEntryService->find($id);

        if ($entry === null) {
            return response()->json(['message' => 'Movimiento no encontrado'], HttpResponse::HTTP_NOT_FOUND);
        }

        $this->financeEntryService->deleteManualEntry($entry);

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
