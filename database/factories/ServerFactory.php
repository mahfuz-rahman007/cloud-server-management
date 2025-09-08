<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        // Pre-defined arrays for efficient rotation (no random calls)
        $prefixes = ['web', 'api', 'db', 'cache', 'worker', 'queue', 'mail', 'file', 'backup', 'monitor'];
        $environments = ['prod', 'staging', 'dev', 'test'];
        $regions = ['us', 'eu', 'asia'];
        $providers = ['aws', 'digitalocean', 'vultr', 'other'];
        $statuses = ['active', 'inactive', 'maintenance'];
        $cpuOptions = [1, 2, 4, 8, 16, 32];
        $ramOptions = [512, 1024, 2048, 4096, 8192, 16384];
        $storageOptions = [20, 50, 100, 250, 500, 1000];

        // Generate realistic server names with counter
        $serverName = $prefixes[$counter % count($prefixes)].'-'.
                     $environments[$counter % count($environments)].'-'.
                     $regions[$counter % count($regions)].'-'.
                     str_pad($counter, 3, '0', STR_PAD_LEFT);

        // Sequential IP generation (much faster than unique() checking)
        $ipSegment3 = floor(($counter - 1) / 254);
        $ipSegment4 = (($counter - 1) % 254) + 1;
        $ipAddress = "10.0.{$ipSegment3}.{$ipSegment4}";

        // Incremental timestamps for realistic spread
        $baseTime = now()->subMonths(6);
        $minutesToAdd = ($counter * 5) + fake()->numberBetween(0, 240); // 5 min intervals + random
        $createdAt = $baseTime->copy()->addMinutes($minutesToAdd);
        $updatedAt = $createdAt->copy()->addMinutes(fake()->numberBetween(0, 60));

        return [
            'name' => $serverName,
            'ip_address' => $ipAddress,
            'provider' => $providers[$counter % count($providers)],
            'status' => $statuses[$counter % count($statuses)],
            'cpu_cores' => $cpuOptions[$counter % count($cpuOptions)],
            'ram_mb' => $ramOptions[$counter % count($ramOptions)],
            'storage_gb' => $storageOptions[$counter % count($storageOptions)],
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }
}
