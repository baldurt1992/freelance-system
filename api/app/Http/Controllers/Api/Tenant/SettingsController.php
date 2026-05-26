<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Application\Settings\TenantSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateTenantSettingsRequest;
use App\Http\Resources\TenantSettingsResource;
use Illuminate\Http\JsonResponse;

final class SettingsController extends Controller
{
    public function __construct(
        private readonly TenantSettingsService $tenantSettingsService,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json(new TenantSettingsResource(tenant()));
    }

    public function update(UpdateTenantSettingsRequest $request): JsonResponse
    {
        $tenant = $this->tenantSettingsService->update($request->validated());

        return response()->json(new TenantSettingsResource($tenant));
    }
}
