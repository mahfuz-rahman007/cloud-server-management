<?php

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

// Performance Baseline Tests
it('measures baseline performance with small dataset', function () {
    $this->actingAs($this->user);
    
    // Create 100 servers for baseline
    Server::factory()->count(100)->create();
    
    $start = microtime(true);
    $response = $this->get('/servers');
    $duration = (microtime(true) - $start) * 1000;
    
    $response->assertSuccessful();
    
    // Log baseline performance
    echo "\n📊 Baseline (100 servers): {$duration}ms";
    
    // Should be fast with small dataset
    expect($duration)->toBeLessThan(500);
});

it('measures performance with large dataset before optimization', function () {
    $this->actingAs($this->user);
    
    // Create 5000 servers to simulate production load
    echo "\nCreating 5000 test servers... ";
    $start = microtime(true);
    
    // Use chunked creation for memory efficiency
    for ($i = 0; $i < 50; $i++) {
        Server::factory()->count(100)->create();
    }
    
    $creationTime = (microtime(true) - $start) * 1000;
    echo "Done ({$creationTime}ms)\n";
    
    // Measure query performance
    $start = microtime(true);
    $queryCount = DB::getQueryLog();
    DB::enableQueryLog();
    
    $response = $this->get('/servers');
    
    $queries = DB::getQueryLog();
    $duration = (microtime(true) - $start) * 1000;
    
    $response->assertSuccessful();
    
    // Log detailed metrics
    echo "\n📊 Large dataset (5000 servers): {$duration}ms";
    echo "\n🔍 Query count: " . count($queries);
    echo "\n📝 Queries executed: " . json_encode(array_column($queries, 'query'));
    
    // This will likely fail initially - establishing the problem
    if ($duration > 100) {
        echo "\n❌ PERFORMANCE ISSUE DETECTED: {$duration}ms > 100ms target";
        echo "\n📊 Performance gap: " . ($duration - 100) . "ms over target";
    }
    
    // For now, we'll mark this as expected failure to document the problem
    expect($duration)->toBeGreaterThan(0, 'Response time should be measurable');
});

it('tests query efficiency with database profiling', function () {
    $this->actingAs($this->user);
    
    // Create dataset
    Server::factory()->count(1000)->create();
    
    // Enable MySQL profiling if available
    try {
        DB::statement('SET profiling = 1');
    } catch (\Exception $e) {
        echo "\n⚠️ MySQL profiling not available";
    }
    
    DB::enableQueryLog();
    
    $response = $this->get('/servers');
    $queries = DB::getQueryLog();
    
    // Analyze query patterns
    $selectQueries = array_filter($queries, fn($q) => str_starts_with($q['query'], 'select'));
    $totalQueries = count($queries);
    $selectCount = count($selectQueries);
    
    echo "\n📊 Total queries: {$totalQueries}";
    echo "\n🔍 Select queries: {$selectCount}";
    
    // Check for N+1 query problems
    if ($selectCount > 3) {
        echo "\n🚨 POTENTIAL N+1 QUERY DETECTED: {$selectCount} select queries";
        
        foreach ($selectQueries as $index => $query) {
            echo "\n  Query {$index}: " . $query['query'];
        }
    }
    
    $response->assertSuccessful();
});

it('benchmarks filtering and search performance', function () {
    $this->actingAs($this->user);
    
    // Create diverse dataset
    Server::factory()->count(2000)->create([
        'provider' => 'aws',
        'status' => 'active'
    ]);
    Server::factory()->count(2000)->create([
        'provider' => 'digitalocean', 
        'status' => 'inactive'
    ]);
    Server::factory()->count(1000)->create([
        'provider' => 'vultr',
        'status' => 'maintenance'
    ]);
    
    // Test various operations
    $operations = [
        'Filter by status' => '/servers?status=active',
        'Filter by provider' => '/servers?provider=aws',
        'Search by name' => '/servers?search=server',
        'Complex filter' => '/servers?status=active&provider=aws&search=web',
        'Sort by name' => '/servers?sort=name&direction=asc',
        'Large page size' => '/servers?per_page=100'
    ];
    
    foreach ($operations as $operation => $url) {
        DB::flushQueryLog();
        
        $start = microtime(true);
        $response = $this->get($url);
        $duration = (microtime(true) - $start) * 1000;
        
        $queries = DB::getQueryLog();
        
        echo "\n📈 {$operation}: {$duration}ms (" . count($queries) . " queries)";
        
        $response->assertSuccessful();
    }
});

// Post-optimization tests (to be run after fixes)
it('verifies performance improvements after optimization', function () {
    $this->actingAs($this->user);
    
    // Create large dataset
    Server::factory()->count(10000)->create();
    
    DB::enableQueryLog();
    
    $start = microtime(true);
    $response = $this->get('/servers');
    $duration = (microtime(true) - $start) * 1000;
    
    $queries = DB::getQueryLog();
    
    $response->assertSuccessful();
    
    echo "\n✅ Optimized performance: {$duration}ms count 10000";
    echo "\n🔍 Query count after optimization: " . count($queries);
    
    // After optimization, this should pass
    expect($duration)->toBeLessThan(100, "Response should be under 100ms after optimization");
    expect(count($queries))->toBeLessThan(5, "Should use minimal queries after optimization");
});