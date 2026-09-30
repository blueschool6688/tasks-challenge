<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Admin can update any task.
     * Regular user can only update tasks assigned to themselves,
     * and cannot reassign the task to another user.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $task = $this->route('task');
        if (! $task instanceof Task) {
            $task = Task::find($this->route('task'));
        }

        if (! $task) {
            return true;
        }

        $newAssignedTo = $this->filled('assigned_to') ? (int) $this->input('assigned_to') : null;

        return $user->can('update', [$task, $newAssignedTo]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'string', 'in:todo,in_progress,done'],
            'assigned_to' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
