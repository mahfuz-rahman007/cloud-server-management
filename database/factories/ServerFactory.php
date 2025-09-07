<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['aws', 'digitalocean', 'vultr', 'other'];
        $statuses = ['active', 'inactive', 'maintenance'];
        
        return [
            'name' => fake()->slug(2) . '-' . fake()->randomElement(['web', 'db', 'cache', 'api', 'worker']),
            'ip_address' => fake()->unique()->ipv4(),
            'provider' => fake()->randomElement($providers),
            'status' => fake()->randomElement($statuses),
            'cpu_cores' => fake()->numberBetween(1, 128),
            'ram_mb' => fake()->randomElement([512, 1024, 2048, 4096, 8192, 16384, 32768, 65536]),
            'storage_gb' => fake()->randomElement([10, 20, 50, 100, 250, 500, 1000, 2000]),
        ];
    }

    /**
     * Create a server with active status.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Create a server with inactive status.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Create a server with maintenance status.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'maintenance',
        ]);
    }
}
