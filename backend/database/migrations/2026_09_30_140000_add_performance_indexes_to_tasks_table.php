<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds composite indexes tailored for high-volume (1M+ rows) task filtering,
     * sorting, and soft-delete scoping.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['assigned_to', 'status', 'deleted_at', 'created_at'], 'idx_tasks_user_filter');
            $table->index(['status', 'deleted_at', 'created_at'], 'idx_tasks_status_filter');
            $table->index(['due_date'], 'idx_tasks_due_date');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_user_filter');
            $table->dropIndex('idx_tasks_status_filter');
            $table->dropIndex('idx_tasks_due_date');
        });
    }
};
