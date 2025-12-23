<?php

use App\Facades\DatabaseConnectionTester;
use App\Livewire\DatabaseServer\Create;
use App\Models\User;
use Livewire\Livewire;

test('guests cannot access create page', function () {
    $this->get(route('database-servers.create'))
        ->assertRedirect(route('login'));
});

test('authenticated users can access create page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('database-servers.create'))
        ->assertStatus(200);
});

test('can create database server with valid data', function () {
    DatabaseConnectionTester::shouldReceive('test')
        ->once()
        ->andReturn([
            'success' => true,
            'message' => 'Successfully connected to the database server!',
        ]);

    $user = User::factory()->create();
    $volume = \App\Models\Volume::create([
        'name' => 'Test Volume',
        'type' => 'local',
        'config' => ['path' => '/var/backups'],
    ]);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('form.name', 'Production MySQL Server')
        ->set('form.host', 'localhost')
        ->set('form.port', 3306)
        ->set('form.database_type', 'mysql')
        ->set('form.username', 'root')
        ->set('form.password', 'secret123')
        ->set('form.database_name', 'myapp_production')
        ->set('form.description', 'Main production database')
        ->set('form.volume_id', $volume->id)
        ->set('form.recurrence', 'daily')
        ->set('form.retention_days', 14)
        ->call('save')
        ->assertRedirect(route('database-servers.index'));

    $this->assertDatabaseHas('database_servers', [
        'name' => 'Production MySQL Server',
        'host' => 'localhost',
        'port' => 3306,
    ]);

    $server = \App\Models\DatabaseServer::where('name', 'Production MySQL Server')->first();
    $this->assertDatabaseHas('backups', [
        'database_server_id' => $server->id,
        'volume_id' => $volume->id,
        'recurrence' => 'daily',
        'retention_days' => 14,
    ]);
});

