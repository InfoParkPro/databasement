<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->json('volume_ids')->nullable()->after('volume_id');
        });

        DB::table('backups')
            ->whereNull('volume_ids')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('backups')
                        ->where('id', $row->id)
                        ->update(['volume_ids' => json_encode([$row->volume_id])]);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn('volume_ids');
        });
    }
};
