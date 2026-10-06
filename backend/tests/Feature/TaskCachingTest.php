<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskCachingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user1 = User::factory()->create(['role' => 'user']);
        $this->user2 = User::factory()->create(['role' => 'user']);
    }

    public function test_first_request_is_cache_miss_and_second_is_cache_hit(): void
    {
        Task::factory()->count(5)->create(['assigned_to' => $this->admin->id]);

        Sanctum::actingAs($this->admin);

        // First call: cache miss
        $res1 = $this->getJson('/api/v1/tasks?page=1&per_page=10');
        $res1->assertStatus(200);
        $res1->assertHeader('X-Cache', 'MISS');

        // Second call: cache hit
        $res2 = $this->getJson('/api/v1/tasks?page=1&per_page=10');
        $res2->assertStatus(200);
        $res2->assertHeader('X-Cache', 'HIT');
        $this->assertEquals($res1->json('data'), $res2->json('data'));
    }

    public function test_creating_task_invalidates_cache_and_increments_version(): void
    {
        $cacheService = app(TaskCacheService::class);
        $initialVersion = $cacheService->getTasksVersion();

        Sanctum::actingAs($this->admin);

        $res1 = $this->getJson('/api/v1/tasks?page=1&per_page=10');
        $res1->assertHeader('X-Cache', 'MISS');

        // Create a new task
        $createRes = $this->postJson('/api/v1/tasks', [
            'title' => 'Brand New Cached Task',
            'status' => 'todo',
            'assigned_to' => $this->admin->id,
        ]);
        $createRes->assertStatus(201);

        // Version must have incremented
        $this->assertGreaterThan($initialVersion, $cacheService->getTasksVersion());

        // Next listing request must be MISS because cache was invalidated
        $res2 = $this->getJson('/api/v1/tasks?page=1&per_page=10');
        $res2->assertHeader('X-Cache', 'MISS');
        $res2->assertJsonPath('meta.total', 1);
    }

    public function test_tenancy_isolation_in_cached_queries(): void
    {
        Task::factory()->create(['assigned_to' => $this->user1->id, 'title' => 'User1 Task']);
        Task::factory()->create(['assigned_to' => $this->user2->id, 'title' => 'User2 Task']);

        // User 1 requests tasks
        Sanctum::actingAs($this->user1);
        $resUser1 = $this->getJson('/api/v1/tasks');
        $resUser1->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'User1 Task');

        // User 2 requests tasks: must NOT receive User 1 cached data
        Sanctum::actingAs($this->user2);
        $resUser2 = $this->getJson('/api/v1/tasks');
        $resUser2->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'User2 Task');
    }
}
