<?php

declare(strict_types=1);

namespace App\Application\Clients;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class ClientService
{
    public function list(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = Client::query()->orderBy('created_at', 'desc');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function find(string|int $id): ?Client
    {
        return Client::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Client
    {
        $client = Client::query()->create($data);

        Log::info('[Clients] created', ['client_id' => $client->id]);

        return $client;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Client $client, array $data): Client
    {
        $client->update($data);

        Log::info('[Clients] updated', ['client_id' => $client->id]);

        return $client->fresh();
    }

    public function delete(Client $client): void
    {
        $clientId = $client->id;

        try {
            $client->delete();
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'FOREIGN KEY')) {
                throw new ConflictHttpException(
                    'No se puede eliminar el cliente porque tiene cotizaciones asociadas.',
                );
            }

            throw $e;
        }

        Log::info('[Clients] deleted', ['client_id' => $clientId]);
    }
}