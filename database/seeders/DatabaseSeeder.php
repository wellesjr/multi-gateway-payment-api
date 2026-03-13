<?php

namespace Database\Seeders;

use App\Models\Gateway;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Gateway::query()->updateOrCreate(
            ['name' => 'gateway1'],
            ['is_active' => true, 'priority' => 1],
        );

        Gateway::query()->updateOrCreate(
            ['name' => 'gateway2'],
            ['is_active' => true, 'priority' => 2],
        );

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
