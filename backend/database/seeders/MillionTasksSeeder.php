<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MillionTasksSeeder extends Seeder
{
    public function run(int $total = 1000000, bool $fresh = false): void
    {
        ini_set('memory_limit', '-1');
        DB::disableQueryLog();
        DB::connection()->unsetEventDispatcher();

        $userIds = User::pluck('id')->toArray();
        if (empty($userIds)) {
            $admin = User::firstOrCreate(
                ['email' => 'admin@example.com'],
                ['name' => 'Admin User', 'password' => 'password', 'role' => 'admin']
            );
            $john = User::firstOrCreate(
                ['email' => 'john@example.com'],
                ['name' => 'John Doe', 'password' => 'password', 'role' => 'user']
            );
            $jane = User::firstOrCreate(
                ['email' => 'jane@example.com'],
                ['name' => 'Jane Smith', 'password' => 'password', 'role' => 'user']
            );
            $userIds = [$admin->id, $john->id, $jane->id];
        }

        $driver = DB::connection()->getDriverName();

        // Optional fresh cleanup
        if ($fresh) {
            $this->command?->info("🧹 Truncating existing tasks table...");
            if ($driver === 'mysql') {
                DB::statement('SET foreign_key_checks = 0;');
                DB::table('tasks')->truncate();
                DB::statement('SET foreign_key_checks = 1;');
            } else {
                DB::table('tasks')->delete();
            }
        }

        // Chunk sizes: 1000 rows for MySQL (8000 params, very low memory), 200 for SQLite
        $chunkSize = ($driver === 'sqlite') ? 200 : 1000;
        $transactionChunkSize = 25000;

        $this->command?->info("🚀 Starting high-speed seed of " . number_format($total) . " tasks (Driver: {$driver}, Chunk size: {$chunkSize})...");

        // Engine optimizations
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA synchronous = OFF;');
            DB::statement('PRAGMA journal_mode = WAL;');
        } elseif ($driver === 'mysql') {
            DB::statement('SET foreign_key_checks = 0;');
            DB::statement('SET unique_checks = 0;');
        }

        $taskPrefixes = [
            'Fix memory leak in', 'Refactor repository pattern for', 'Implement caching strategy for',
            'Add integration tests for', 'Optimize SQL queries in', 'Setup CI/CD deployment for',
            'Upgrade framework dependencies in', 'Harden authentication security on',
            'Design GraphQL schema for', 'Implement rate limiting on', 'Resolve race condition in',
            'Migrate legacy worker queues in', 'Add OpenAPI documentation for', 'Audit permissions in',
        ];

        $taskModules = [
            'Billing & Subscriptions Service', 'User Authentication Layer', 'Task Notification Dispatcher',
            'Search Index Engine', 'Export / Import Worker', 'File Storage S3 Adapter',
            'Webhook Event Handler', 'Activity Audit Logger', 'Analytics Aggregate Pipeline',
            'Realtime WebSocket Gateway', 'PDF Invoice Generator', 'Email Delivery Microservice',
        ];

        $statuses = ['todo', 'in_progress', 'done'];
        $userCount = count($userIds);
        $prefixCount = count($taskPrefixes);
        $moduleCount = count($taskModules);

        $now = date('Y-m-d H:i:s');
        $startTime = microtime(true);

        $progressBar = $this->command?->getOutput()->createProgressBar($total);
        $progressBar?->start();

        $batch = [];
        $insertedSoFar = 0;

        DB::beginTransaction();

        for ($i = 1; $i <= $total; $i++) {
            $prefix = $taskPrefixes[$i % $prefixCount];
            $module = $taskModules[($i / 3) % $moduleCount];
            $title = "{$prefix} {$module} #{$i}";

            $status = $statuses[$i % 3];
            $userId = $userIds[$i % $userCount];

            // Varied due dates: ~20% null, others between past 30 days and next 60 days
            $dueOffset = ($i % 90) - 30;
            $dueDate = ($i % 5 === 0) ? null : date('Y-m-d', strtotime("{$dueOffset} days"));

            // Varied creation timestamps across past 180 days
            $createdDaysAgo = $i % 180;
            $createdAt = date('Y-m-d H:i:s', strtotime("-{$createdDaysAgo} days"));

            // ~3.5% soft-deleted tasks to evaluate soft-delete indexing
            $deletedAt = ($i % 29 === 0) ? $now : null;

            $batch[] = [
                'title' => $title,
                'description' => "Detailed engineering description for task #{$i} related to {$module}. Stress test payload for high volume query evaluation.",
                'status' => $status,
                'assigned_to' => $userId,
                'due_date' => $dueDate,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'deleted_at' => $deletedAt,
            ];

            if (count($batch) >= $chunkSize) {
                DB::table('tasks')->insert($batch);
                $insertedSoFar += count($batch);
                $progressBar?->advance(count($batch));
                $batch = [];

                // Commit periodically and force garbage collection to keep memory flat (~25MB)
                if ($insertedSoFar % $transactionChunkSize === 0) {
                    DB::commit();
                    gc_collect_cycles();
                    DB::beginTransaction();
                }
            }
        }

        if (!empty($batch)) {
            DB::table('tasks')->insert($batch);
            $progressBar?->advance(count($batch));
            $batch = [];
        }

        DB::commit();
        gc_collect_cycles();

        if ($driver === 'mysql') {
            DB::statement('SET foreign_key_checks = 1;');
            DB::statement('SET unique_checks = 1;');
        }

        $progressBar?->finish();
        $this->command?->newLine(2);

        $duration = round(microtime(true) - $startTime, 2);
        $rate = round($total / max(0.01, $duration));
        $peakMem = round(memory_get_peak_usage(true) / 1024 / 1024, 1);

        $this->command?->info(" Successfully seeded " . number_format($total) . " tasks in {$duration}s (~" . number_format($rate) . " rows/sec | Peak RAM: {$peakMem} MB)!");
    }
}
