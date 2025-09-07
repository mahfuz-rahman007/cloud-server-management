<?php

use App\Models\Server;

// Model Configuration Tests
it('has correct fillable attributes', function () {
    $fillable = [
        'name',
        'ip_address',
        'provider',
        'status',
        'cpu_cores',
        'ram_mb',
        'storage_gb',
    ];

    expect((new Server)->getFillable())->toBe($fillable);
});

it('has correct casts configuration', function () {
    $server = new Server;
    $casts = $server->getCasts();

    expect($casts)->toHaveKey('cpu_cores');
    expect($casts)->toHaveKey('ram_mb');
    expect($casts)->toHaveKey('storage_gb');
    expect($casts['cpu_cores'])->toBe('integer');
    expect($casts['ram_mb'])->toBe('integer');
    expect($casts['storage_gb'])->toBe('integer');
});

it('has correct table name', function () {
    $server = new Server;
    expect($server->getTable())->toBe('servers');
});

// Factory Definition Tests (without database)
it('has factory definition with correct structure', function () {
    $factory = Server::factory();
    $definition = $factory->definition();

    expect($definition)->toHaveKey('name');
    expect($definition)->toHaveKey('ip_address');
    expect($definition)->toHaveKey('provider');
    expect($definition)->toHaveKey('status');
    expect($definition)->toHaveKey('cpu_cores');
    expect($definition)->toHaveKey('ram_mb');
    expect($definition)->toHaveKey('storage_gb');
});

it('factory creates valid data types', function () {
    $factory = Server::factory();
    $definition = $factory->definition();

    expect($definition['name'])->toBeString();
    expect($definition['provider'])->toBeIn(['aws', 'digitalocean', 'vultr', 'other']);
    expect($definition['status'])->toBeIn(['active', 'inactive', 'maintenance']);
    expect($definition['cpu_cores'])->toBeInt();
    expect($definition['ram_mb'])->toBeInt();
    expect($definition['storage_gb'])->toBeInt();
    expect($definition['cpu_cores'])->toBeGreaterThanOrEqual(1);
    expect($definition['cpu_cores'])->toBeLessThanOrEqual(128);
});

// Attribute Testing (without database)
it('sets attributes correctly', function () {
    $server = new Server([
        'name' => 'test-server',
        'ip_address' => '192.168.1.100',
        'provider' => 'aws',
        'status' => 'active',
        'cpu_cores' => 4,
        'ram_mb' => 8192,
        'storage_gb' => 100,
    ]);

    expect($server->name)->toBe('test-server');
    expect($server->ip_address)->toBe('192.168.1.100');
    expect($server->provider)->toBe('aws');
    expect($server->status)->toBe('active');
    expect($server->cpu_cores)->toBe(4);
    expect($server->ram_mb)->toBe(8192);
    expect($server->storage_gb)->toBe(100);
});
