<?php

namespace Database\Factories;

use App\Models\FirmwareVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class FirmwareVersionFactory extends Factory
{
    protected $model = FirmwareVersion::class;

    public function definition(): array
    {
        return [
            'version' => 'v' . $this->faker->numberBetween(1, 10) . '.' . 
                         $this->faker->numberBetween(0, 9) . '.' . 
                         $this->faker->numberBetween(0, 99),
            'file_path' => 'firmware/' . $this->faker->bothify('fw-????-####.bin'),
            'file_size' => $this->faker->numberBetween(1000000, 50000000),
            'checksum' => hash('sha256', $this->faker->uuid()),
            'model' => $this->faker->bothify('Model-???-###'),
            'description' => $this->faker->sentence(),
            'release_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'is_active' => $this->faker->boolean(70),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function latest(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'release_date' => now()->subDays(rand(1, 7)),
        ]);
    }
}
