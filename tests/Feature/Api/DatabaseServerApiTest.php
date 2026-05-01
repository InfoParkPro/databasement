<?php

use App\Models\DatabaseServer;
use App\Models\User;
use App\Models\Volume;

test('unauthenticated users cannot access database servers api', function () {
    $response = $this->getJson('/api/v1/database-servers');

    $response->assertUnauthorized();
});

test('authenticated users can list database servers via api', function () {
    $user = User::factory()->create();
    DatabaseServer::factory()->count(3)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/database-servers');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'host',
                    'port',
                    'database_type',
                    'description',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links',
            'meta',
        ]);
});

test('authenticated users can filter database servers by name', function () {
    $user = User::factory()->create();
    DatabaseServer::factory()->create(['name' => 'Production MySQL']);
    DatabaseServer::factory()->create(['name' => 'Staging PostgreSQL']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/database-servers?filter[name]=Production');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Production MySQL');
});

test('authenticated users can filter database servers by database type', function () {
    $user = User::factory()->create();
    DatabaseServer::factory()->create(['database_type' => 'mysql']);
    DatabaseServer::factory()->create(['database_type' => 'postgres']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/database-servers?filter[database_type]=mysql');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.database_type', 'mysql');
});

test('authenticated users can filter database servers by host', function () {
    $user = User::factory()->create();
    DatabaseServer::factory()->create(['host' => 'localhost']);
    DatabaseServer::factory()->create(['host' => 'db.example.com']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/database-servers?filter[host]=localhost');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.host', 'localhost');
});

test('authenticated users can sort database servers', function () {
    $user = User::factory()->create();
    DatabaseServer::factory()->create(['name' => 'Alpha Server']);
    DatabaseServer::factory()->create(['name' => 'Beta Server']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/database-servers?sort=name');

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Alpha Server')
        ->assertJsonPath('data.1.name', 'Beta Server');
});

test('authenticated users can get a specific database server', function () {
    $user = User::factory()->create();
    $server = DatabaseServer::factory()->create(['name' => 'Test Server']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/database-servers/{$server->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $server->id)
        ->assertJsonPath('data.name', 'Test Server');
});

test('show endpoint includes backup schedule details', function () {
    $user = User::factory()->create();
    $server = DatabaseServer::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/database-servers/{$server->id}");

    $response->assertOk()
        ->assertJsonPath('data.backups.0.backup_schedule.name', 'Daily')
        ->assertJsonPath('data.backups.0.backup_schedule.expression', '0 2 * * *');
});

test('show endpoint includes configured backups with volume ids', function () {
    $user = User::factory()->create();
    $firstVolume = Volume::factory()->local()->create();
    $secondVolume = Volume::factory()->s3()->create();

    $server = DatabaseServer::factory()->create();
    $firstBackup = $server->backups()->firstOrFail();
    $firstBackup->update(['volume_id' => $firstVolume->id]);
    $server->backups()->create([
        'volume_id' => $secondVolume->id,
        'backup_schedule_id' => $firstBackup->backup_schedule_id,
        'retention_policy' => $firstBackup->retention_policy,
        'retention_days' => $firstBackup->retention_days,
        'database_selection_mode' => $firstBackup->database_selection_mode,
        'database_names' => ['secondary_db'],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/database-servers/{$server->id}");

    $response->assertOk()
        ->assertJsonPath('data.backups.0.volume_id', $firstVolume->id)
        ->assertJsonPath('data.backups.1.volume_id', $secondVolume->id);
});

test('show endpoint includes legacy volume_id on backup payload', function () {
    $user = User::factory()->create();
    $firstVolume = Volume::factory()->local()->create();

    $server = DatabaseServer::factory()->create();
    $server->backups()->firstOrFail()->update([
        'volume_id' => $firstVolume->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/database-servers/{$server->id}");

    $response->assertOk()
        ->assertJsonPath('data.backups.0.volume_id', $firstVolume->id);
});

test('password is not exposed in database server api response', function () {
    $user = User::factory()->create();
    $server = DatabaseServer::factory()->create(['password' => 'secret-password']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/database-servers/{$server->id}");

    $response->assertOk()
        ->assertJsonMissing(['password' => 'secret-password']);
});
