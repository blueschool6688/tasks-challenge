<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        $john = User::firstOrCreate(
            ['email' => 'john@example.com'],
            [
                'name' => 'John Doe',
                'password' => 'password',
                'role' => 'user',
            ]
        );

        $jane = User::firstOrCreate(
            ['email' => 'jane@example.com'],
            [
                'name' => 'Jane Smith',
                'password' => 'password',
                'role' => 'user',
            ]
        );

        // 2. Create Sample Tasks (12 tasks with mixed statuses and assignees)
        $tasks = [
            [
                'title' => 'Set up CI/CD pipeline for backend testing',
                'description' => 'Configure GitHub Actions workflow to run PHPUnit and phpstan on every pull request.',
                'status' => 'done',
                'assigned_to' => $admin->id,
                'due_date' => now()->subDays(3)->format('Y-m-d'),
            ],
            [
                'title' => 'Design database schema for Team Task Manager',
                'description' => 'Define tables for users and tasks with foreign keys and soft deletes.',
                'status' => 'done',
                'assigned_to' => $admin->id,
                'due_date' => now()->subDays(1)->format('Y-m-d'),
            ],
            [
                'title' => 'Implement Sanctum API Token Authentication',
                'description' => 'Create endpoints POST /api/login and POST /api/logout with plain text token generation.',
                'status' => 'in_progress',
                'assigned_to' => $admin->id,
                'due_date' => now()->addDays(2)->format('Y-m-d'),
            ],
            [
                'title' => 'Audit security headers and CORS configuration',
                'description' => 'Ensure HTTPS redirection, rate limiting, and strict CORS whitelist for the SPA frontend.',
                'status' => 'todo',
                'assigned_to' => $admin->id,
                'due_date' => now()->addDays(7)->format('Y-m-d'),
            ],
            [
                'title' => 'Build Vue 3 Login Page with Vuetify',
                'description' => 'Create reactive form with email and password validation rules and error alerts.',
                'status' => 'done',
                'assigned_to' => $john->id,
                'due_date' => now()->subDays(2)->format('Y-m-d'),
            ],
            [
                'title' => 'Implement Pinia Auth Store and Axios Interceptors',
                'description' => 'Store token in localStorage, attach Bearer header, and redirect on 401 response.',
                'status' => 'in_progress',
                'assigned_to' => $john->id,
                'due_date' => now()->addDays(1)->format('Y-m-d'),
            ],
            [
                'title' => 'Create Tasks Data Table view with status chips',
                'description' => 'Use v-data-table with server-side pagination, search debouncing, and status color mapping.',
                'status' => 'todo',
                'assigned_to' => $john->id,
                'due_date' => now()->addDays(5)->format('Y-m-d'),
            ],
            [
                'title' => 'Write unit tests for frontend auth store',
                'description' => 'Test login, logout, and automatic token rehydration from localStorage with Vitest.',
                'status' => 'todo',
                'assigned_to' => $john->id,
                'due_date' => null,
            ],
            [
                'title' => 'Create TaskForm modal dialog component',
                'description' => 'Support both create and edit modes with Vuetify date picker and assignee dropdown.',
                'status' => 'in_progress',
                'assigned_to' => $jane->id,
                'due_date' => now()->addDays(3)->format('Y-m-d'),
            ],
            [
                'title' => 'Implement global notification snackbar',
                'description' => 'Show quick feedback toast on task creation, update, and deletion success or error.',
                'status' => 'done',
                'assigned_to' => $jane->id,
                'due_date' => now()->subDays(1)->format('Y-m-d'),
            ],
            [
                'title' => 'Test role-based access control on frontend navigation',
                'description' => 'Ensure regular users cannot access admin actions or view other members tasks.',
                'status' => 'todo',
                'assigned_to' => $jane->id,
                'due_date' => now()->addDays(4)->format('Y-m-d'),
            ],
            [
                'title' => 'Document API endpoints and write interview answers',
                'description' => 'Complete README.md and ANSWERS.md with architectural depth and code samples.',
                'status' => 'todo',
                'assigned_to' => $jane->id,
                'due_date' => null,
            ],
        ];

        foreach ($tasks as $taskData) {
            Task::firstOrCreate(['title' => $taskData['title']], $taskData);
        }
    }
}
