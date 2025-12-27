<?php

use App\Models\Backup;
use App\Models\DatabaseServer;
use App\Models\Volume;
use App\Services\DemoBackupService;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('first user can create demo backup during registration', function () {
    // Mock the DemoBackupService to verify it's called
    $mockService = Mockery::mock(DemoBackupService::class);
    $mockService->shouldReceive('createDemoBackup')
        ->once()
        ->andReturn(DatabaseServer::factory()->make());

    $this->app->instance(DemoBackupService::class, $mockService);

    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'create_demo_backup' => '1',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('demo backup is not created for non-first users', function () {
    // Create existing user first
    \App\Models\User::factory()->create();

    // Mock to verify service is NOT called
    $mockService = Mockery::mock(DemoBackupService::class);
    $mockService->shouldNotReceive('createDemoBackup');

    $this->app->instance(DemoBackupService::class, $mockService);

    $response = $this->post(route('register.store'), [
        'name' => 'Second User',
        'email' => 'second@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'create_demo_backup' => '1', // Even if checked, should not be called for non-first users
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});
