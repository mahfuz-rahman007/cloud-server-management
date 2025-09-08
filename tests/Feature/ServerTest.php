<?php

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

// Authentication Tests
it('requires authentication for server endpoints', function () {
    $this->get('/servers')->assertRedirect('/login');
    $this->post('/servers')->assertRedirect('/login');
    $this->get('/servers/1')->assertRedirect('/login');
    $this->put('/servers/1')->assertRedirect('/login');
    $this->delete('/servers/1')->assertRedirect('/login');
});

// CRUD Operations Tests
it('can list servers with pagination', function () {
    $this->actingAs($this->user);

    Server::factory()->count(30)->create();

    $response = $this->get('/servers');

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Servers/Index')
            ->has('servers.data', 25) // Default pagination
            ->has('pagination')
        );
});

it('validates index request parameters', function () {
    $this->actingAs($this->user);

    // Test invalid status
    $response = $this->get('/servers?status=invalid');
    $response->assertSessionHasErrors(['status']);

    // Test invalid provider
    $response = $this->get('/servers?provider=invalid');
    $response->assertSessionHasErrors(['provider']);

    // Test invalid sort field
    $response = $this->get('/servers?sort=invalid_field');
    $response->assertSessionHasErrors(['sort']);

    // Test invalid direction
    $response = $this->get('/servers?direction=invalid');
    $response->assertSessionHasErrors(['direction']);

    // Test invalid per_page
    $response = $this->get('/servers?per_page=500');
    $response->assertSessionHasErrors(['per_page']);

    // Test invalid page (negative)
    $response = $this->get('/servers?page=-1');
    $response->assertSessionHasErrors(['page']);
});

it('accepts valid index request parameters', function () {
    $this->actingAs($this->user);

    $response = $this->get('/servers?status=active&provider=aws&search=web&sort=name&direction=asc&per_page=50&page=1');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Servers/Index')
        ->has('servers.data')
        ->has('filters')
        ->has('pagination')
    );
});

it('handles empty search gracefully', function () {
    $this->actingAs($this->user);

    $response = $this->get('/servers?search=');
    $response->assertSuccessful();
});

it('handles special characters in search', function () {
    $this->actingAs($this->user);

    $response = $this->get('/servers?search='.urlencode('web-server@test.com'));
    $response->assertSuccessful();
});

it('can create server with valid data', function () {
    $this->actingAs($this->user);

    $serverData = [
        'name' => 'web-server-01',
        'ip_address' => '192.168.1.100',
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 4,
        'ram_mb' => 8192,
        'storage_gb' => 100,
    ];

    $response = $this->post('/servers', $serverData);

    $response->assertRedirect('/servers')
        ->assertSessionHas('success', 'Server created successfully.');

    $this->assertDatabaseHas('servers', $serverData);
});

it('validates required fields when creating server', function () {
    $this->actingAs($this->user);

    $response = $this->post('/servers', []);

    $response->assertSessionHasErrors([
        'name', 'ip_address', 'provider', 'status',
        'cpu_cores', 'ram_mb', 'storage_gb',
    ]);
});

it('validates ip address format', function () {
    $this->actingAs($this->user);

    $response = $this->post('/servers', [
        'name' => 'test-server',
        'ip_address' => 'invalid-ip',
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 4,
        'ram_mb' => 8192,
        'storage_gb' => 100,
    ]);

    $response->assertSessionHasErrors(['ip_address']);
});

it('validates resource limits', function () {
    $this->actingAs($this->user);

    $response = $this->post('/servers', [
        'name' => 'test-server',
        'ip_address' => '192.168.1.100',
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 0, // Invalid: below minimum
        'ram_mb' => 256, // Invalid: below minimum
        'storage_gb' => 5, // Invalid: below minimum
    ]);

    $response->assertSessionHasErrors(['cpu_cores', 'ram_mb', 'storage_gb']);
});

it('cannot create server with duplicate ip address', function () {
    $this->actingAs($this->user);

    Server::factory()->create(['ip_address' => '192.168.1.100']);

    $response = $this->post('/servers', [
        'name' => 'test-server',
        'ip_address' => '192.168.1.100', // Duplicate
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 4,
        'ram_mb' => 8192,
        'storage_gb' => 100,
    ]);

    $response->assertSessionHasErrors(['ip_address']);
});

it('cannot create server with duplicate name per provider', function () {
    $this->actingAs($this->user);

    Server::factory()->create([
        'name' => 'web-server',
        'provider' => 'aws',
    ]);

    $response = $this->post('/servers', [
        'name' => 'web-server', // Duplicate name
        'ip_address' => '192.168.1.101',
        'provider' => 'aws', // Same provider
        'status' => 'active',
        'cpu_cores' => 4,
        'ram_mb' => 8192,
        'storage_gb' => 100,
    ]);

    $response->assertSessionHasErrors(['name']);
});

it('can create server with same name on different provider', function () {
    $this->actingAs($this->user);

    Server::factory()->create([
        'name' => 'web-server',
        'provider' => 'aws',
    ]);

    $serverData = [
        'name' => 'web-server', // Same name
        'ip_address' => '192.168.1.101',
        'provider' => 'digitalocean', // Different provider
        'status' => 'active',
        'cpu_cores' => 4,
        'ram_mb' => 8192,
        'storage_gb' => 100,
    ];

    $response = $this->post('/servers', $serverData);

    $response->assertRedirect('/servers');
    $this->assertDatabaseHas('servers', $serverData);
});

it('can show single server', function () {
    $this->actingAs($this->user);

    $server = Server::factory()->create();

    $response = $this->get("/servers/{$server->id}");

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Servers/Show')
            ->has('server')
        );
});

it('can update server', function () {
    $this->actingAs($this->user);

    $server = Server::factory()->create();

    $updateData = [
        'name' => 'updated-server',
        'ip_address' => '192.168.1.200',
        'provider' => 'digitalocean',
        'status' => 'maintenance',
        'cpu_cores' => 8,
        'ram_mb' => 16384,
        'storage_gb' => 500,
        'updated_at' => $server->updated_at->format('Y-m-d H:i:s'), // Include version control
    ];

    $response = $this->put("/servers/{$server->id}", $updateData);

    $response->assertRedirect('/servers');
    $this->assertDatabaseHas('servers', array_merge(['id' => $server->id], $updateData));
});

it('can delete server', function () {
    $this->actingAs($this->user);

    $server = Server::factory()->create();

    $response = $this->delete("/servers/{$server->id}");

    $response->assertRedirect('/servers');
    $this->assertDatabaseMissing('servers', ['id' => $server->id]);
});

// Filtering and Search Tests
it('can filter servers by status', function () {
    $this->actingAs($this->user);

    Server::factory()->active()->count(5)->create();
    Server::factory()->inactive()->count(3)->create();
    Server::factory()->maintenance()->count(2)->create();

    $response = $this->get('/servers?status=active');

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Servers/Index')
            ->has('servers.data', 5)
        );
});

it('can filter servers by provider', function () {
    $this->actingAs($this->user);

    Server::factory()->count(3)->create(['provider' => 'aws']);
    Server::factory()->count(2)->create(['provider' => 'digitalocean']);

    $response = $this->get('/servers?provider=aws');

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Servers/Index')
            ->has('servers.data', 3)
        );
});

it('can search servers by name', function () {
    $this->actingAs($this->user);

    Server::factory()->create(['name' => 'web-server-01']);
    Server::factory()->create(['name' => 'db-server-01']);
    Server::factory()->create(['name' => 'api-server-01']);

    $response = $this->get('/servers?search=web');

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Servers/Index')
            ->has('servers.data', 1)
        );
});

it('can search servers by ip address', function () {
    $this->actingAs($this->user);

    Server::factory()->create(['ip_address' => '192.168.1.100']);
    Server::factory()->create(['ip_address' => '10.0.0.100']);

    $response = $this->get('/servers?search=192.168');

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Servers/Index')
            ->has('servers.data', 1)
        );
});

it('can sort servers by any field', function () {
    $this->actingAs($this->user);

    $server1 = Server::factory()->create(['name' => 'a-server']);
    $server2 = Server::factory()->create(['name' => 'z-server']);

    $response = $this->get('/servers?sort=name&direction=asc');

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Servers/Index')
            ->where('servers.data.0.name', 'a-server')
            ->where('servers.data.1.name', 'z-server')
        );
});

// Bulk Operations Tests
it('can bulk delete servers', function () {
    $this->actingAs($this->user);

    $servers = Server::factory()->count(3)->create();
    $serverIds = $servers->pluck('id')->toArray();

    $response = $this->delete('/servers/bulk-destroy', [
        'ids' => $serverIds,
    ]);

    $response->assertRedirect('/servers');

    foreach ($serverIds as $id) {
        $this->assertDatabaseMissing('servers', ['id' => $id]);
    }
});

it('can bulk update server status', function () {
    $this->actingAs($this->user);

    $servers = Server::factory()->active()->count(3)->create();
    $serverIds = $servers->pluck('id')->toArray();

    $response = $this->patch('/servers/bulk-update-status', [
        'ids' => $serverIds,
        'status' => 'maintenance',
    ]);

    $response->assertRedirect('/servers');

    foreach ($serverIds as $id) {
        $this->assertDatabaseHas('servers', [
            'id' => $id,
            'status' => 'maintenance',
        ]);
    }
});
