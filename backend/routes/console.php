<?php

use Database\Seeders\MillionTasksSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('tasks:seed-million {count=1000000 : Number of tasks to seed} {--fresh : Truncate existing tasks before seeding}', function () {
    $count = (int) $this->argument('count');
    $fresh = (bool) $this->option('fresh');
    $seeder = new MillionTasksSeeder();
    $seeder->setCommand($this);
    $seeder->run($count, $fresh);
})->purpose('Seed high-volume tasks for performance benchmarking');
