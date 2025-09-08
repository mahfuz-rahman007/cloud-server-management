<?php

use App\Models\Server;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->server = Server::factory()->create([
        'name' => 'test-server',
        'ip_address' => '192.168.1.100',
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 4,
        'ram_mb' => 8192,
        'storage_gb' => 100,
    ]);
});

it('prevents race condition when server is modified by another user', function () {
    $this->actingAs($this->user);

    // Get the original updated_at timestamp when the user opens the edit form
    $originalUpdatedAt = $this->server->updated_at->format('Y-m-d H:i:s');

    // Simulate a small delay then another user updating the server
    // This represents what would happen if another user saved changes while
    // the current user had the edit form open
    sleep(1); // Ensure different timestamp
    $this->server->update(['name' => 'modified-by-another-user']);
    $this->server->refresh();

    // Now try to update with the original timestamp (simulating race condition)
    $response = $this->put("/servers/{$this->server->id}", [
        'name' => 'my-update',
        'ip_address' => '192.168.1.100',
        'provider' => 'aws',
        'status' => 'inactive',  // Different status change
        'cpu_cores' => 8,        // Different CPU change
        'ram_mb' => 8192,
        'storage_gb' => 100,
        'updated_at' => $originalUpdatedAt, // Old timestamp
    ]);

    // Should redirect back with validation errors
    $response->assertRedirect();
    $response->assertSessionHasErrors(['updated_at']);

    // Verify the error message
    $errors = session('errors')->getBag('default');
    expect($errors->has('updated_at'))->toBeTrue();
    expect($errors->first('updated_at'))->toBe('This server was modified by another user. Please refresh and try again.');

    // Verify the server data was NOT changed by our attempted update
    $this->server->refresh();
    expect($this->server->name)->toBe('modified-by-another-user');
    expect($this->server->status)->toBe('active'); // Should still be original status
    expect($this->server->cpu_cores)->toBe(4);     // Should still be original CPU
});

it('allows update when timestamp matches current version', function () {
    $this->actingAs($this->user);

    // Get the current updated_at timestamp
    $currentUpdatedAt = $this->server->updated_at->format('Y-m-d H:i:s');

    // Update with the correct current timestamp
    $response = $this->put("/servers/{$this->server->id}", [
        'name' => 'successful-update',
        'ip_address' => '192.168.1.101',
        'provider' => 'digitalocean',
        'status' => 'maintenance',
        'cpu_cores' => 8,
        'ram_mb' => 16384,
        'storage_gb' => 200,
        'updated_at' => $currentUpdatedAt, // Correct timestamp
    ]);

    // Should redirect to servers index with success message
    $response->assertRedirect('/servers');
    $response->assertSessionHas('success');

    // Verify the server was updated
    $this->server->refresh();
    expect($this->server->name)->toBe('successful-update');
    expect($this->server->ip_address)->toBe('192.168.1.101');
    expect($this->server->provider)->toBe('digitalocean');
    expect($this->server->status)->toBe('maintenance');
    expect($this->server->cpu_cores)->toBe(8);
    expect($this->server->ram_mb)->toBe(16384);
    expect($this->server->storage_gb)->toBe(200);
});

it('handles concurrent updates with different timestamps', function () {
    $this->actingAs($this->user);

    // Create a second server for testing
    $server2 = Server::factory()->create([
        'name' => 'concurrent-test',
        'ip_address' => '10.0.0.1',
    ]);

    $originalTimestamp1 = $this->server->updated_at->format('Y-m-d H:i:s');
    $originalTimestamp2 = $server2->updated_at->format('Y-m-d H:i:s');

    // Update server1 first (this should succeed)
    $response1 = $this->put("/servers/{$this->server->id}", [
        'name' => 'first-update',
        'ip_address' => '192.168.1.100',
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 4,
        'ram_mb' => 8192,
        'storage_gb' => 100,
        'updated_at' => $originalTimestamp1,
    ]);

    $response1->assertRedirect('/servers');
    $response1->assertSessionHas('success');

    // Update server2 with its original timestamp (this should also succeed)
    $response2 = $this->put("/servers/{$server2->id}", [
        'name' => 'second-update',
        'ip_address' => '10.0.0.1',
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 2,
        'ram_mb' => 4096,
        'storage_gb' => 50,
        'updated_at' => $originalTimestamp2,
    ]);

    $response2->assertRedirect('/servers');
    $response2->assertSessionHas('success');

    // Verify both servers were updated correctly
    $this->server->refresh();
    $server2->refresh();

    expect($this->server->name)->toBe('first-update');
    expect($server2->name)->toBe('second-update');
});

it('works with json api requests', function () {
    $this->actingAs($this->user);

    // Get original timestamp
    $originalUpdatedAt = $this->server->updated_at->format('Y-m-d H:i:s');

    // Simulate another user modifying the server
    sleep(1); // Ensure different timestamp
    $this->server->update(['status' => 'maintenance']);
    $this->server->refresh();

    // Try to update via API with old timestamp
    $response = $this->putJson("/servers/{$this->server->id}", [
        'name' => 'api-update',
        'ip_address' => '192.168.1.100',
        'provider' => 'aws',
        'status' => 'inactive',
        'cpu_cores' => 4,
        'ram_mb' => 8192,
        'storage_gb' => 100,
        'updated_at' => $originalUpdatedAt,
    ]);

    // Should return validation error in JSON format
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['updated_at']);

    $responseData = $response->json();
    expect($responseData['errors']['updated_at'][0])
        ->toBe('This server was modified by another user. Please refresh and try again.');
});
