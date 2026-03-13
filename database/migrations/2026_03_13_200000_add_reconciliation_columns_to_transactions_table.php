<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('reconciliation_status', ['pending', 'reconciled', 'failed'])->default('pending');
            $table->timestamp('reconciled_at')->nullable();
            $table->text('reconciliation_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['reconciliation_status', 'reconciled_at', 'reconciliation_error']);
        });
    }
};
