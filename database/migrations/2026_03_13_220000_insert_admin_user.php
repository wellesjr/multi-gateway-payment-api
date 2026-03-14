<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->insert([
            'name' => 'administrador',
            'email' => 'admin_master@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'ADMIN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin_master@gmail.com')->delete();
    }
};
