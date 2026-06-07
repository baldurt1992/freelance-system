<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Application\Finances\FinanceCategoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFinanceCategoryRequest;
use App\Http\Requests\Finance\UpdateFinanceCategoryRequest;
use App\Http\Resources\FinanceCategoryResource;
use App\Models\FinanceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class FinanceCategoryController extends Controller
{
    public function __construct(
        private readonly FinanceCategoryService $financeCategoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $categories = $this->financeCategoryService->list(is_string($type) ? $type : null);

        return response()->json([
            'data' => FinanceCategoryResource::collection($categories),
        ]);
    }

    public function store(StoreFinanceCategoryRequest $request): JsonResponse
    {
        $category = $this->financeCategoryService->create($request->validated());

        return response()->json(new FinanceCategoryResource($category), HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateFinanceCategoryRequest $request, string $id): JsonResponse
    {
        $category = $this->findCategoryOrNotFoundResponse($id);
        if ($category instanceof JsonResponse) {
            return $category;
        }

        $updated = $this->financeCategoryService->update($category, $request->validated());

        return response()->json(new FinanceCategoryResource($updated));
    }

    public function destroy(string $id): JsonResponse
    {
        $category = $this->findCategoryOrNotFoundResponse($id);
        if ($category instanceof JsonResponse) {
            return $category;
        }

        $this->financeCategoryService->delete($category);

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    private function findCategoryOrNotFoundResponse(string $id): FinanceCategory|JsonResponse
    {
        $category = $this->financeCategoryService->find((int) $id);

        if ($category === null) {
            return $this->notFoundResponse('Categoría no encontrada');
        }

        return $category;
    }
}
