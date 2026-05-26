<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

abstract class Controller
{
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        AnonymousResourceCollection $resourceCollection,
    ): JsonResponse {
        return response()->json([
            'data' => $resourceCollection,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    protected function notFoundResponse(string $message): JsonResponse
    {
        return response()->json(['message' => $message], HttpResponse::HTTP_NOT_FOUND);
    }
}
