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
        Schema::create('database_servers', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name');
            $table->string('host')->nullable();
            $table->integer('port')->default(3306);
            $table->string('database_type')->default('mysql');
            $table->string('sqlite_path')->nullable();
            $table->json('database_names')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->boolean('backup_all_databases')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_servers');
    }
};
