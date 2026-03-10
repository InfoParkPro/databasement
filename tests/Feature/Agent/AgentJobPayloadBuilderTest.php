<?php

use App\Models\DatabaseServer;
use App\Models\Snapshot;
use App\Services\Agent\AgentJobPayloadBuilder;

test('resolveBackupPath replaces date placeholders', function () {
    $this->travelTo(now()->setDate(2026, 3, 15));

    $server = DatabaseServer::factory()->create([
        'database_names' => ['testdb'],
    ]);
    $server->backup->update(['path' => 'backups/{year}/{month}/{day}']);

    $snapshot = Snapshot::factory()->forServer($server)->create([
        'database_name' => 'testdb',
    ]);

    $builder = new AgentJobPayloadBuilder;
    $payload = $builder->build($snapshot);

    expect($payload['backup_path'])->toBe('backups/2026/03/15');
});

test('resolveBackupPath returns empty string when path is empty', function () {
    $server = DatabaseServer::factory()->create([
        'database_names' => ['testdb'],
    ]);
    $server->backup->update(['path' => '']);

    $snapshot = Snapshot::factory()->forServer($server)->create([
        'database_name' => 'testdb',
    ]);

    $builder = new AgentJobPayloadBuilder;
    $payload = $builder->build($snapshot);

    expect($payload['backup_path'])->toBe('');
});
