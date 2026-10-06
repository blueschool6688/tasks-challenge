<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Admin can assign to any user.
     * Regular user can only assign task to themselves.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $assignedTo = $this->filled('assigned_to') ? (int) $this->input('assigned_to') : null;

        return $user->can('create', [\App\Models\Task::class, $assignedTo]);
    }

    /**
     * Prepare the data for validation.
     *
     * Sanitize user input by stripping HTML tags and trimming whitespace.
     */
    protected function prepareForValidation(): void
    {
        $sanitized = [];

        if ($this->has('title') && is_string($this->input('title'))) {
            $sanitized['title'] = trim(strip_tags($this->input('title')));
        }

        if ($this->has('description') && is_string($this->input('description'))) {
            $sanitized['description'] = trim(strip_tags($this->input('description')));
        }

        if (! empty($sanitized)) {
            $this->merge($sanitized);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:todo,in_progress,done'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
