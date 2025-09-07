<?php

namespace Database\Seeders;

use App\Models\Server;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating servers for performance testing...');
        
        // Create 5000 servers for performance testing
        Server::factory(5000)->create();
        
        // Create some specific test servers with known data
        Server::factory()->create([
            'name' => 'production-web-01',
            'ip_address' => '192.168.1.100',
            'provider' => 'aws',
            'status' => 'active',
            'cpu_cores' => 4,
            'ram_mb' => 8192,
            'storage_gb' => 100,
        ]);

        Server::factory()->create([
            'name' => 'staging-db-01',
            'ip_address' => '192.168.1.101',
            'provider' => 'digitalocean',
            'status' => 'maintenance',
            'cpu_cores' => 8,
            'ram_mb' => 16384,
            'storage_gb' => 500,
        ]);

        $this->command->info('Server seeding completed!');
    }
}
