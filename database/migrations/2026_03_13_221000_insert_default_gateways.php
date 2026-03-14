<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('gateways')->updateOrInsert(
            ['name' => 'gateway1'],
            ['is_active' => true, 'priority' => 1, 'updated_at' => now(), 'created_at' => now()]
        );

        DB::table('gateways')->updateOrInsert(
            ['name' => 'gateway2'],
            ['is_active' => true, 'priority' => 2, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('gateways')->whereIn('name', ['gateway1', 'gateway2'])->delete();
    }
};
