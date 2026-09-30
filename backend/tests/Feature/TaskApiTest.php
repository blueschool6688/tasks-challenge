<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
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

    public function test_users_list_endpoint(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_admin_can_view_all_tasks(): void
    {
        Task::factory()->create(['assigned_to' => $this->user1->id, 'title' => 'User1 Task']);
        Task::factory()->create(['assigned_to' => $this->user2->id, 'title' => 'User2 Task']);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_regular_user_can_only_view_own_tasks(): void
    {
        Task::factory()->create(['assigned_to' => $this->user1->id, 'title' => 'User1 Task']);
        Task::factory()->create(['assigned_to' => $this->user2->id, 'title' => 'User2 Task']);

        Sanctum::actingAs($this->user1);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'User1 Task');
    }

    public function test_filtering_tasks_by_status(): void
    {
        Task::factory()->create(['assigned_to' => $this->admin->id, 'status' => 'todo', 'title' => 'Todo Task']);
        Task::factory()->create(['assigned_to' => $this->admin->id, 'status' => 'done', 'title' => 'Done Task']);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/tasks?status=done');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'done');
    }

    public function test_searching_tasks_by_title(): void
    {
        Task::factory()->create(['assigned_to' => $this->admin->id, 'title' => 'Fix payment gateway bug']);
        Task::factory()->create(['assigned_to' => $this->admin->id, 'title' => 'Write documentation']);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/tasks?search=payment');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Fix payment gateway bug');
    }

    public function test_admin_can_create_task_for_any_user(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Assigned by Admin',
            'description' => 'Test description',
            'status' => 'todo',
            'assigned_to' => $this->user2->id,
            'due_date' => '2026-10-15',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Assigned by Admin')
            ->assertJsonPath('data.assigned_to', $this->user2->id);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Assigned by Admin',
            'assigned_to' => $this->user2->id,
        ]);
    }

    public function test_regular_user_can_create_task_for_themselves(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Self Assigned Task',
            'status' => 'in_progress',
            'assigned_to' => $this->user1->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.assigned_to', $this->user1->id);
    }

    public function test_regular_user_cannot_create_task_for_another_user(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Illegal Task',
            'status' => 'todo',
            'assigned_to' => $this->user2->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_store_task_validation(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/tasks', [
            'status' => 'invalid-status',
            'assigned_to' => 99999, // non-existent
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'status', 'assigned_to']);
    }

    public function test_admin_can_update_any_task(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->user1->id,
            'title' => 'Old Title',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson('/api/tasks/'.$task->id, [
            'title' => 'Updated by Admin',
            'assigned_to' => $this->user2->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated by Admin')
            ->assertJsonPath('data.assigned_to', $this->user2->id);
    }

    public function test_regular_user_can_update_own_task(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->user1->id,
            'title' => 'User Task',
            'status' => 'todo',
        ]);

        Sanctum::actingAs($this->user1);

        $response = $this->putJson('/api/tasks/'.$task->id, [
            'title' => 'User Task Updated',
            'status' => 'done',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'done');
    }

    public function test_regular_user_cannot_update_other_user_task(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->user2->id,
            'title' => 'User 2 Task',
        ]);

        Sanctum::actingAs($this->user1);

        $response = $this->putJson('/api/tasks/'.$task->id, [
            'title' => 'Hacked Task',
        ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_reassign_task_to_another_user(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->user1->id,
            'title' => 'User 1 Task',
        ]);

        Sanctum::actingAs($this->user1);

        $response = $this->putJson('/api/tasks/'.$task->id, [
            'assigned_to' => $this->user2->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_soft_delete_any_task(): void
    {
        $task = Task::factory()->create(['assigned_to' => $this->user1->id]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson('/api/tasks/'.$task->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_regular_user_can_soft_delete_own_task(): void
    {
        $task = Task::factory()->create(['assigned_to' => $this->user1->id]);

        Sanctum::actingAs($this->user1);

        $response = $this->deleteJson('/api/tasks/'.$task->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_regular_user_cannot_delete_other_user_task(): void
    {
        $task = Task::factory()->create(['assigned_to' => $this->user2->id]);

        Sanctum::actingAs($this->user1);

        $response = $this->deleteJson('/api/tasks/'.$task->id);

        $response->assertStatus(403);
        $this->assertNotSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_deleted_task_not_returned_in_index(): void
    {
        $task = Task::factory()->create(['assigned_to' => $this->admin->id]);
        $task->delete();

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
