<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class TaskCacheService
{
    private const TASKS_VERSION_KEY = 'tasks:version';
    private const USERS_VERSION_KEY = 'users:version';

    private bool $wasCacheHit = false;

    /**
     * Get the current tasks cache version.
     */
    public function getTasksVersion(): int
    {
        try {
            return (int) Cache::get(self::TASKS_VERSION_KEY, 1);
        } catch (Throwable $e) {
            Log::warning('Redis getTasksVersion failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Atomically increment tasks cache version to invalidate cached task listings.
     */
    public function incrementTasksVersion(): void
    {
        try {
            if (Cache::has(self::TASKS_VERSION_KEY)) {
                Cache::increment(self::TASKS_VERSION_KEY);
            } else {
                Cache::forever(self::TASKS_VERSION_KEY, 2);
            }
        } catch (Throwable $e) {
            Log::warning('Redis incrementTasksVersion failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the current users cache version.
     */
    public function getUsersVersion(): int
    {
        try {
            return (int) Cache::get(self::USERS_VERSION_KEY, 1);
        } catch (Throwable $e) {
            Log::warning('Redis getUsersVersion failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Atomically increment users cache version to invalidate cached users dropdown.
     */
    public function incrementUsersVersion(): void
    {
        try {
            if (Cache::has(self::USERS_VERSION_KEY)) {
                Cache::increment(self::USERS_VERSION_KEY);
            } else {
                Cache::forever(self::USERS_VERSION_KEY, 2);
            }
        } catch (Throwable $e) {
            Log::warning('Redis incrementUsersVersion failed: ' . $e->getMessage());
        }
    }

    /**
     * Cache and retrieve total task count for pagination.
     *
     * @param array<string, mixed> $normalizedFilters
     */
    public function rememberTotalCount(string $scope, array $normalizedFilters, Closure $resolver): int
    {
        $version = $this->getTasksVersion();
        $filterHash = md5(json_encode($normalizedFilters, JSON_THROW_ON_ERROR));
        $cacheKey = sprintf('tasks:v%d:count:%s:%s', $version, $scope, $filterHash);

        try {
            if (Cache::has($cacheKey)) {
                $this->wasCacheHit = true;
                return (int) Cache::get($cacheKey);
            }

            $count = (int) $resolver();
            Cache::put($cacheKey, $count, now()->addMinutes(10));
            return $count;
        } catch (Throwable $e) {
            Log::warning('Redis rememberTotalCount failed, falling back to database: ' . $e->getMessage());
            return (int) $resolver();
        }
    }

    /**
     * Cache and retrieve task page items for high-traffic initial pages (pages 1 to 3, no search).
     *
     * @param array<string, mixed> $normalizedFilters
     */
    public function rememberPageItems(
        string $scope,
        array $normalizedFilters,
        int $page,
        int $perPage,
        string $sortBy,
        string $sortOrder,
        Closure $resolver
    ): mixed {
        // Only cache pages 1 to 3 when there is no free-text search filter
        $hasSearch = ! empty($normalizedFilters['search']);
        if ($page > 3 || $hasSearch) {
            return $resolver();
        }

        $version = $this->getTasksVersion();
        $filterHash = md5(json_encode($normalizedFilters, JSON_THROW_ON_ERROR));
        $cacheKey = sprintf(
            'tasks:v%d:page:%s:%s:%d:%d:%s:%s',
            $version,
            $scope,
            $filterHash,
            $page,
            $perPage,
            $sortBy,
            $sortOrder
        );

        try {
            if (Cache::has($cacheKey)) {
                $this->wasCacheHit = true;
                return Cache::get($cacheKey);
            }

            $items = $resolver();
            Cache::put($cacheKey, $items, now()->addSeconds(60));
            return $items;
        } catch (Throwable $e) {
            Log::warning('Redis rememberPageItems failed, falling back to database: ' . $e->getMessage());
            return $resolver();
        }
    }

    /**
     * Cache and retrieve the users list for assignee dropdowns.
     */
    public function rememberUsersList(Closure $resolver): mixed
    {
        $version = $this->getUsersVersion();
        $cacheKey = sprintf('users:v%d:list', $version);

        try {
            if (Cache::has($cacheKey)) {
                $this->wasCacheHit = true;
                return Cache::get($cacheKey);
            }

            $users = $resolver();
            Cache::put($cacheKey, $users, now()->addHour());
            return $users;
        } catch (Throwable $e) {
            Log::warning('Redis rememberUsersList failed, falling back to database: ' . $e->getMessage());
            return $resolver();
        }
    }

    /**
     * Whether the last cache retrieval operation was a cache hit.
     */
    public function wasCacheHit(): bool
    {
        return $this->wasCacheHit;
    }
}
