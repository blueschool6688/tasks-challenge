<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TaskCacheService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly TaskCacheService $cacheService
    ) {}

    /**
     * Get list of users (id, name, email, role) for assigning tasks.
     */
    public function index(): JsonResponse
    {
        $users = $this->cacheService->rememberUsersList(function () {
            return User::select(['id', 'name', 'email', 'role'])
                ->orderBy('name', 'asc')
                ->get();
        });

        return response()->json($users, 200);
    }
}
