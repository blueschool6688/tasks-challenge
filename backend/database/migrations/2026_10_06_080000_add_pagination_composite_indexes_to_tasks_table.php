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
     * Adds composite indexes optimized for default and scoped paginated listings
     * on high-volume datasets (1,000,000+ rows).
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Composite index for admin default listing: WHERE deleted_at IS NULL ORDER BY created_at DESC
            $table->index(['deleted_at', 'created_at'], 'idx_tasks_deleted_created');

            // Composite index for member default listing: WHERE assigned_to = ? AND deleted_at IS NULL ORDER BY created_at DESC
            $table->index(['assigned_to', 'deleted_at', 'created_at'], 'idx_tasks_assigned_deleted_created');

            // Composite index for due date sorting with soft delete: WHERE deleted_at IS NULL ORDER BY due_date ASC/DESC
            $table->index(['deleted_at', 'due_date'], 'idx_tasks_deleted_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_deleted_created');
            $table->dropIndex('idx_tasks_assigned_deleted_created');
            $table->dropIndex('idx_tasks_deleted_due_date');
        });
    }
};
