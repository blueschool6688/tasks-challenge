<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    /**
     * Display a listing of the tasks.
     *
     * Admin: sees all tasks (can filter by assigned_to, status, search).
     * Regular User: scoped only to tasks assigned to themselves.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Task::with('assignee');

        // Role-based scoping
        if (! $user->isAdmin()) {
            $query->where('assigned_to', $user->id);
        } elseif ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->query('assigned_to'));
        }

        // Status filter (?status=todo|in_progress|done)
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Search by title or description (?search=...)
        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = (string) $request->query('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->query('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'title', 'status', 'due_date', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min(100, $perPage));

        $tasks = $query->paginate($perPage);

        return TaskResource::collection($tasks);
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
    public function show(Request $request, Task $task): JsonResponse|TaskResource
    {
        $user = $request->user();

        if (! $user->isAdmin() && (int) $task->assigned_to !== (int) $user->id) {
            return response()->json([
                'message' => 'This action is unauthorized.',
            ], 403);
        }

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
        $user = $request->user();

        if (! $user->isAdmin() && (int) $task->assigned_to !== (int) $user->id) {
            return response()->json([
                'message' => 'This action is unauthorized.',
            ], 403);
        }

        $task->delete();

        return response()->json(null, 204);
    }
}
