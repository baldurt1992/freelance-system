<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class ProjectService
{
    public function list(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = Project::query()
            ->orderBy('created_at', 'desc');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('quote_number', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function find(string|int $id): ?Project
    {
        return Project::query()->with('quote', 'payments', 'billingDocument')->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Project
    {
        $client = Client::query()->find($data['client_id']);

        if ($client === null) {
            throw new ModelNotFoundException('Cliente no encontrado');
        }

        $currency = (string) (tenant()->currency ?? 'COP');

        $project = Project::query()->create(
            ProjectSnapshotFactory::forManualCreate($client, $data, $currency),
        );

        return $project->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
