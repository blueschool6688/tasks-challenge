<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('tasks', 'ft_tasks_search')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->fullText(['title', 'description'], 'ft_tasks_search');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('tasks', 'ft_tasks_search')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropFullText('ft_tasks_search');
            });
        }
    }
};