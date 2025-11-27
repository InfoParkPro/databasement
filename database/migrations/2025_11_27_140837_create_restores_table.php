<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restores', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Relationships
            $table->foreignUlid('snapshot_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('target_server_id')->constrained('database_servers')->cascadeOnDelete();

            // Restore Details
            $table->string('schema_name'); // Target database name
            $table->string('job_id')->nullable(); // Laravel queue job ID

            // Execution Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Status & Result
            $table->enum('status', ['queued', 'running', 'completed', 'failed'])->default('queued');
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();

            // Audit
            $table->foreignUlid('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restores');
    }
};
