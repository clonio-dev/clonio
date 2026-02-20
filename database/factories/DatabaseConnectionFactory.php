<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DatabaseConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DatabaseConnection>
 */
class DatabaseConnectionFactory extends Factory
{
    protected $model = DatabaseConnection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['mysql', 'pgsql']);
        $port = $this->faker->randomElement(['3306', '5432']);

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true) . ' Database',
            'type' => $type,
            'host' => $this->faker->domainName(),
            'port' => $port,
            'database' => $this->faker->word() . '_db',
            'username' => $this->faker->userName(),
            'password' => 'password', // Will be encrypted by model
            'is_production_stage' => true,
        ];
    }

    /**
     * State: MySQL connection
     */
    public function mysql(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'mysql',
            'port' => 3306,
        ]);
    }

    /**
     * State: MariaDB connection
     */
    public function mariadb(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'mariadb',
            'port' => 3306,
        ]);
    }

    /**
     * State: PostgreSQL connection
     */
    public function pgsql(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'pgsql',
            'port' => 5432,
        ]);
    }

    /**
     * State: SQL Server connection
     */
    public function sqlsrv(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'sqlsrv',
            'port' => 1433,
        ]);
    }

    /**
     * State: Test database
     */
    public function testDatabase(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_production_stage' => false,
        ]);
    }

    /**
     * State: Tested connection
     */
    public function tested(): static
    {
        return $this->state(fn (array $attributes): array => [
            'last_tested_at' => now(),
            'is_connectable' => true,
            'last_test_result' => 'Healthy',
        ]);
    }

    /**
     * State: SQLite (for testing)
     */
    public function sqlite(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'sqlite',
            'host' => ':memory:',
            'port' => 0,
            'database' => ':memory:',
            'username' => '',
            'password' => '',
        ]);
    }
}
