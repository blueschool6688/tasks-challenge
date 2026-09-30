<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks.
     * Note: Non-admin query scoping is enforced in TaskController.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the specific task.
     */
    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin() || (int) $task->assigned_to === (int) $user->id;
    }

    /**
     * Determine whether the user can create tasks.
     */
    public function create(User $user, ?int $assignedTo = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Regular user can only assign tasks to themselves
        return $assignedTo === null || $assignedTo === (int) $user->id;
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task, ?int $newAssignedTo = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Regular user can only update their own task
        if ((int) $task->assigned_to !== (int) $user->id) {
            return false;
        }

        // Regular user cannot reassign the task to another user
        if ($newAssignedTo !== null && $newAssignedTo !== (int) $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || (int) $task->assigned_to === (int) $user->id;
    }
}
