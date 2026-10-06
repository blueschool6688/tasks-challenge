<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'idx_tasks_deleted_created'          => ['deleted_at', 'created_at'],
        'idx_tasks_assigned_deleted_created' => ['assigned_to', 'deleted_at', 'created_at'],
        'idx_tasks_user_filter'              => ['assigned_to', 'status', 'deleted_at', 'created_at'],
        'idx_tasks_status_filter'            => ['status', 'deleted_at', 'created_at'],
        'idx_tasks_deleted_due_date'         => ['deleted_at', 'due_date'],
    ];

    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            foreach ($this->indexes as $name => $columns) {
                if (! Schema::hasIndex('tasks', $name)) {
                    $table->index($columns, $name);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            foreach (array_keys($this->indexes) as $name) {
                if (Schema::hasIndex('tasks', $name)) {
                    $table->dropIndex($name);
                }
            }
        });
    }
};