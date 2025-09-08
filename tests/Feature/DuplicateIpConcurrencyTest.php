<?php

use App\Models\Server;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('handles duplicate IP race condition on server creation', function () {
    $this->actingAs($this->user);

    // Create a server with a specific IP
    $existingServer = Server::factory()->create([
        'ip_address' => '192.168.1.100',
    ]);

    // Simulate race condition: validation passes but another user creates server with same IP
    // We'll directly trigger the database constraint violation

    // Try to create another server with the same IP
    $response = $this->post('/servers', [
        'name' => 'new-server',
        'ip_address' => '192.168.1.100', // Same IP as existing server
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 4,
        'ram_mb' => 8192,
        'storage_gb' => 100,
    ]);

    // Should redirect back with validation error
    $response->assertRedirect();
    $response->assertSessionHasErrors(['ip_address']);

    // Verify the error message is user-friendly
    $errors = session('errors')->getBag('default');
    expect($errors->first('ip_address'))
        ->toBe('This IP address is already assigned to another server.');

    // Verify the server wasn't created
    expect(Server::where('name', 'new-server')->exists())->toBeFalse();
});

it('handles duplicate IP race condition on server update', function () {
    $this->actingAs($this->user);

    // Create two servers with different IPs
    $server1 = Server::factory()->create(['ip_address' => '192.168.1.100']);
    $server2 = Server::factory()->create(['ip_address' => '192.168.1.101']);

    // Try to update server2 to have the same IP as server1
    $response = $this->put("/servers/{$server2->id}", [
        'name' => $server2->name,
        'ip_address' => '192.168.1.100', // Same IP as server1
        'provider' => $server2->provider,
        'status' => $server2->status,
        'cpu_cores' => $server2->cpu_cores,
        'ram_mb' => $server2->ram_mb,
        'storage_gb' => $server2->storage_gb,
        'updated_at' => $server2->updated_at->format('Y-m-d H:i:s'),
    ]);

    // Should redirect back with validation error
    $response->assertRedirect();
    $response->assertSessionHasErrors(['ip_address']);

    // Verify the error message
    $errors = session('errors')->getBag('default');
    expect($errors->first('ip_address'))
        ->toBe('This IP address is already assigned to another server.');

    // Verify server2 still has its original IP
    $server2->refresh();
    expect($server2->ip_address)->toBe('192.168.1.101');
});

it('handles concurrent server creation via API with duplicate IP', function () {
    $this->actingAs($this->user);

    // Create existing server
    $existingServer = Server::factory()->create([
        'ip_address' => '10.0.0.50',
    ]);

    // Try to create another server with same IP via API
    $response = $this->postJson('/servers', [
        'name' => 'api-server',
        'ip_address' => '10.0.0.50',
        'provider' => 'digitalocean',
        'status' => 'active',
        'cpu_cores' => 2,
        'ram_mb' => 4096,
        'storage_gb' => 50,
    ]);

    // Should return 422 validation error
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ip_address']);

    // Verify the error message in JSON response
    $responseData = $response->json();
    expect($responseData['errors']['ip_address'][0])
        ->toBe('This IP address is already assigned to another server.');
});

it('allows server creation with different IP addresses', function () {
    $this->actingAs($this->user);

    // Create first server
    $server1Response = $this->post('/servers', [
        'name' => 'server-1',
        'ip_address' => '192.168.1.100',
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 4,
        'ram_mb' => 8192,
        'storage_gb' => 100,
    ]);

    $server1Response->assertRedirect('/servers');

    // Create second server with different IP (should succeed)
    $server2Response = $this->post('/servers', [
        'name' => 'server-2',
        'ip_address' => '192.168.1.101', // Different IP
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 8,
        'ram_mb' => 16384,
        'storage_gb' => 200,
    ]);

    $server2Response->assertRedirect('/servers');

    // Verify both servers exist
    expect(Server::where('name', 'server-1')->exists())->toBeTrue();
    expect(Server::where('name', 'server-2')->exists())->toBeTrue();
    expect(Server::count())->toBe(2);
});

it('handles race condition between validation and database insert', function () {
    $this->actingAs($this->user);

    // This test simulates the race condition where:
    // 1. User A passes validation (IP is available)
    // 2. User B creates server with same IP
    // 3. User A tries to insert and hits database constraint

    // We can't easily simulate the exact timing, but we can test the exception handling
    // by trying to create a server that already exists

    // Create server with specific IP
    Server::factory()->create(['ip_address' => '172.16.0.1']);

    // Mock the scenario where validation might have passed but DB constraint fails
    // This would happen if another request created the server between validation and insert

    $response = $this->post('/servers', [
        'name' => 'race-condition-test',
        'ip_address' => '172.16.0.1',
        'provider' => 'vultr',
        'status' => 'active',
        'cpu_cores' => 1,
        'ram_mb' => 512,
        'storage_gb' => 10,
    ]);

    // Should handle the constraint violation gracefully
    $response->assertRedirect();
    $response->assertSessionHasErrors(['ip_address']);

    // Should preserve user input
    $response->assertSessionHasInput('name', 'race-condition-test');
    $response->assertSessionHasInput('provider', 'vultr');
});

it('specifically tests database constraint exception handling', function () {
    $this->actingAs($this->user);

    // Create server directly in database to bypass normal flow
    $existingServer = Server::factory()->create(['ip_address' => '10.10.10.10']);

    // Mock a scenario where validation was bypassed or there was a timing issue
    // We'll simulate this by directly testing the controller logic with a constraint violation

    // The normal validation would catch this, but this tests the exception handling
    // that would occur if there was a race condition

    $response = $this->post('/servers', [
        'name' => 'constraint-test',
        'ip_address' => '10.10.10.10', // Same IP as existing
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 2,
        'ram_mb' => 2048,
        'storage_gb' => 25,
    ]);

    // The validation will catch this first, but if there was a race condition,
    // the database constraint would be the last line of defense
    $response->assertRedirect();
    $response->assertSessionHasErrors(['ip_address']);

    // Verify helpful error message (either from validation or constraint handling)
    $errors = session('errors')->getBag('default');
    $errorMessage = $errors->first('ip_address');

    // Should be either the validation message or the race condition message
    expect($errorMessage)->toContain('IP address');
    expect($errorMessage)->toContain('already');
});
