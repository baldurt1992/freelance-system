<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Application\Clients\ClientService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Requests\Client\UploadClientAvatarRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clientService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $paginator = $this->clientService->list(
            search: is_string($search) ? $search : null,
        );

        return $this->paginatedResponse(
            $paginator,
            ClientResource::collection($paginator->items()),
        );
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->create($request->validated());

        return response()->json(new ClientResource($client), HttpResponse::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $client = $this->findClientOrNotFoundResponse($id);
        if ($client instanceof JsonResponse) {
            return $client;
        }

        return response()->json(new ClientResource($client));
    }

    public function update(UpdateClientRequest $request, string $id): JsonResponse
    {
        $client = $this->findClientOrNotFoundResponse($id);
        if ($client instanceof JsonResponse) {
            return $client;
        }

        $updated = $this->clientService->update($client, $request->validated());

        return response()->json(new ClientResource($updated));
    }

    public function destroy(string $id): JsonResponse
    {
        $client = $this->findClientOrNotFoundResponse($id);
        if ($client instanceof JsonResponse) {
            return $client;
        }

        $this->clientService->delete($client);

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function uploadAvatar(UploadClientAvatarRequest $request, string $id): JsonResponse
    {
        $client = $this->findClientOrNotFoundResponse($id);
        if ($client instanceof JsonResponse) {
            return $client;
        }

        $avatar = $request->file('avatar');
        if (! $avatar instanceof UploadedFile) {
            return response()->json(['message' => 'Invalid avatar upload'], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $path = $avatar->store('avatars', 'public');
        $updated = $this->clientService->update($client, ['avatar' => $path]);

        return response()->json(new ClientResource($updated));
    }

    private function findClientOrNotFoundResponse(string $id): Client|JsonResponse
    {
        $client = $this->clientService->find($id);

        if ($client === null) {
            return $this->notFoundResponse('Client not found');
        }

        return $client;
    }
}
