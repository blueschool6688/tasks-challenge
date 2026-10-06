<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskCacheService $cacheService
    ) {}


    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Task::query()
            ->select(['id', 'title', 'description', 'status', 'assigned_to', 'due_date', 'created_at', 'updated_at'])
            ->with('assignee:id,name,email,role');

        $scope = $user->isAdmin() ? 'all' : 'u' . $user->id;

        if (! $user->isAdmin()) {
            $query->where('assigned_to', $user->id);
        } elseif ($request->filled('assigned_to')) {
            $query->where('assigned_to', (int) $request->query('assigned_to'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = (string) $request->query('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->query('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'created_at', 'due_date', 'status'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $sortBy = 'created_at';
            $sortOrder = 'desc';
            $query->orderBy('created_at', 'desc');
        }

        if ($sortBy !== 'id') {
            $query->orderBy('id', $sortOrder);
        }

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int) $request->query('page', 1));

        if ($request->query('pagination') === 'simple') {
            $tasks = $query->simplePaginate($perPage);
            return TaskResource::collection($tasks)
                ->response()
                ->header('X-Cache', 'BYPASS');
        }

        $normalizedFilters = [
            'assigned_to' => ! $user->isAdmin() ? $user->id : ($request->filled('assigned_to') ? (int) $request->query('assigned_to') : null),
            'status' => $request->filled('status') ? (string) $request->query('status') : null,
            'search' => $request->filled('search') ? trim((string) $request->query('search')) : null,
        ];

        $total = $this->cacheService->rememberTotalCount($scope, $normalizedFilters, function () use ($query): int {
            return (clone $query)->count();
        });

        $items = $this->cacheService->rememberPageItems(
            $scope,
            $normalizedFilters,
            $page,
            $perPage,
            $sortBy,
            $sortOrder,
            function () use ($query, $page, $perPage) {
                return $query->forPage($page, $perPage)->get();
            }
        );

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        return TaskResource::collection($paginator)
            ->response()
            ->header('X-Cache', $this->cacheService->wasCacheHit() ? 'HIT' : 'MISS');
    }

    /**
     * Store a newly created task.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::create($request->validated());
        $task->load('assignee');

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified task.
     */
    public function show(Request $request, Task $task): TaskResource
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $task);

        $task->load('assignee');

        return new TaskResource($task);
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());
        $task->load('assignee');

        return new TaskResource($task);
    }

    /**
     * Remove the specified task (soft delete).
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $task);

        $task->delete();

        return response()->json(null, 204);
    }
}
