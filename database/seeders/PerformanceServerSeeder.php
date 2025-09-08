<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerformanceServerSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * High-performance seeding for 5000+ server records
     * Uses timestamp-based uniqueness for safe multiple runs
     */
    public function run(): void
    {
        $batchSize = 1000;
        $totalRecords = 10000;
        $batches = ceil($totalRecords / $batchSize);

        // Generate unique session ID based on current timestamp
        $sessionId = substr(md5(microtime(true)), 0, 8);
        echo "Generating {$totalRecords} servers with session ID: {$sessionId}\n";

        for ($batch = 0; $batch < $batches; $batch++) {
            $records = [];
            $startIndex = ($batch * $batchSize) + 1;
            $endIndex = min($startIndex + $batchSize - 1, $totalRecords);

            for ($i = $startIndex; $i <= $endIndex; $i++) {
                $records[] = $this->generateServerRecord($i, $sessionId);
            }

            // Batch insert for maximum performance
            DB::table('servers')->insert($records);

            echo 'Batch '.($batch + 1)."/{$batches} completed (".count($records)." records)\n";
        }

        echo "✅ Successfully generated {$totalRecords} servers\n";
    }

    /**
     * Generate a single server record with session-based uniqueness
     */
    private function generateServerRecord(int $index, string $sessionId): array
    {
        // Pre-defined arrays for efficient rotation
        $prefixes = ['web', 'api', 'db', 'cache', 'worker', 'queue', 'mail', 'file', 'backup', 'monitor'];
        $environments = ['prod', 'staging', 'dev', 'test'];
        $regions = ['us-east', 'us-west', 'eu-central', 'asia-pacific'];
        $providers = ['aws', 'digitalocean', 'vultr', 'other'];
        $statuses = ['active', 'inactive', 'maintenance'];
        $cpuOptions = [1, 2, 4, 8, 16, 32];
        $ramOptions = [512, 1024, 2048, 4096, 8192, 16384];
        $storageOptions = [20, 50, 100, 250, 500, 1000];

        // Generate guaranteed unique IP using session hash + sequential pattern
        $sessionHash = crc32($sessionId) & 0xFFFF; // 16-bit hash from session
        $ipBase = 0x0A000000; // 10.0.0.0 in hex
        $ipOffset = ($sessionHash << 16) + $index; // Combine session + index

        $ipAddress = long2ip($ipBase + $ipOffset);

        // Generate unique server name with session ID
        $prefixIndex = $index % count($prefixes);
        $envIndex = floor($index / count($prefixes)) % count($environments);
        $regionIndex = floor($index / (count($prefixes) * count($environments))) % count($regions);

        $serverName = $prefixes[$prefixIndex].'-'.
                     $environments[$envIndex].'-'.
                     $regions[$regionIndex].'-'.
                     substr($sessionId, 0, 4).'-'.
                     str_pad($index, 3, '0', STR_PAD_LEFT);

        // Varied timestamps based on session and index
        $baseTime = now()->subMonths(6);
        $sessionOffset = (hexdec(substr($sessionId, 4, 4)) % 43200); // 0-30 days offset
        $minutesToAdd = $sessionOffset + ($index * 2) + ($index % 45);
        $createdAt = $baseTime->copy()->addMinutes($minutesToAdd);
        $updatedAt = $createdAt->copy()->addMinutes($index % 90);

        return [
            'name' => $serverName,
            'ip_address' => $ipAddress,
            'provider' => $providers[$index % count($providers)],
            'status' => $statuses[$index % count($statuses)],
            'cpu_cores' => $cpuOptions[$index % count($cpuOptions)],
            'ram_mb' => $ramOptions[$index % count($ramOptions)],
            'storage_gb' => $storageOptions[$index % count($storageOptions)],
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
