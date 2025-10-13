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
            'manufacturer' => $this->faker->randomElement(['TP-Link', 'Huawei', 'ZTE', 'Nokia', 'Ericsson']),
            'model' => $this->faker->bothify('Model-???-###'),
            'file_path' => 'firmware/' . $this->faker->bothify('fw-????-####.bin'),
            'file_hash' => hash('sha256', $this->faker->uuid()),
            'file_size' => $this->faker->numberBetween(1000000, 50000000),
            'release_notes' => $this->faker->sentence(),
            'changelog' => $this->faker->optional()->paragraph(),
            'is_stable' => $this->faker->boolean(30),
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
            'is_stable' => true,
        ]);
    }
    
    public function stable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_stable' => true,
        ]);
    }
}
