<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update any existing 'queued' status to 'pending'
        DB::table('backup_jobs')
            ->where('status', 'queued')
            ->update(['status' => 'pending']);

        // For MySQL, modify the enum to remove 'queued'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE backup_jobs MODIFY COLUMN status ENUM('pending', 'running', 'completed', 'failed') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For MySQL, add 'queued' back to the enum
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE backup_jobs MODIFY COLUMN status ENUM('pending', 'queued', 'running', 'completed', 'failed') NOT NULL");
        }
    }
};
