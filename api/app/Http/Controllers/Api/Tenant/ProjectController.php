<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Application\Projects\ProjectPaymentService;
use App\Application\Projects\ProjectService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\MarkProjectPaidRequest;
use App\Http\Requests\Project\RegisterPaymentRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectPaymentResource;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly ProjectPaymentService $paymentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $paginator = $this->projectService->list(
            search: is_string($search) ? $search : null,
        );

        return response()->json([
            'data' => ProjectResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create($request->validated());

        return response()->json(new ProjectResource($project), HttpResponse::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $project = $this->projectService->find($id);

        if ($project === null) {
            return response()->json(['message' => 'Proyecto no encontrado'], HttpResponse::HTTP_NOT_FOUND);
        }

        return response()->json(new ProjectResource($project));
    }

    public function update(UpdateProjectRequest $request, string $id): JsonResponse
    {
        $project = $this->projectService->find($id);

        if ($project === null) {
            return response()->json(['message' => 'Proyecto no encontrado'], HttpResponse::HTTP_NOT_FOUND);
        }

        $updated = $this->projectService->update($project, $request->validated());

        return response()->json(new ProjectResource($updated));
    }

    public function destroy(string $id): JsonResponse
    {
        $project = $this->projectService->find($id);

        if ($project === null) {
            return response()->json(['message' => 'Proyecto no encontrado'], HttpResponse::HTTP_NOT_FOUND);
        }

        $this->projectService->delete($project);

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function registerPayment(RegisterPaymentRequest $request, string $id): JsonResponse
    {
        $project = $this->projectService->find($id);

        if ($project === null) {
            return response()->json(['message' => 'Proyecto no encontrado'], HttpResponse::HTTP_NOT_FOUND);
        }

        $result = $this->paymentService->registerPartialPayment(
            $project,
            (int) $request->validated('amount_cents'),
            $request->validated('paid_at'),
        );

        return response()->json([
            'project' => new ProjectResource($result['project']),
            'payment' => new ProjectPaymentResource($result['payment']),
        ], HttpResponse::HTTP_CREATED);
    }

    public function markPaid(MarkProjectPaidRequest $request, string $id): JsonResponse
    {
        $project = $this->projectService->find($id);

        if ($project === null) {
            return response()->json(['message' => 'Proyecto no encontrado'], HttpResponse::HTTP_NOT_FOUND);
        }

        $result = $this->paymentService->markProjectPaid(
            $project,
            $request->validated('paid_at'),
        );

        return response()->json([
            'project' => [
                'id' => $result['project']->id,
                'is_fully_paid' => $result['project']->is_fully_paid,
                'paid_total_cents' => $result['project']->paid_total_cents,
                'balance_due_cents' => $result['project']->balance_due_cents,
            ],
            'payment' => $result['payment'] !== null
                ? new ProjectPaymentResource($result['payment'])
                : null,
        ]);
    }

    public function payments(string $id): JsonResponse
    {
        $project = $this->projectService->find($id);

        if ($project === null) {
            return response()->json(['message' => 'Proyecto no encontrado'], HttpResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => ProjectPaymentResource::collection($project->payments),
        ]);
    }
}
