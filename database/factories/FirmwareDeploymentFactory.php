<?php

namespace Database\Factories;

use App\Models\CpeDevice;
use App\Models\FirmwareDeployment;
use App\Models\FirmwareVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class FirmwareDeploymentFactory extends Factory
{
    protected $model = FirmwareDeployment::class;

    public function definition(): array
    {
        return [
            'firmware_version_id' => FirmwareVersion::factory(),
            'cpe_device_id' => CpeDevice::factory(),
            'status' => 'scheduled',
            'download_progress' => 0,
            'scheduled_at' => now(),
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ];
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(5),
            'error_message' => $this->faker->sentence(),
        ]);
    }

    public function downloading(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'downloading',
            'started_at' => now()->subMinutes(5),
            'download_progress' => $this->faker->numberBetween(10, 90),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(15),
            'completed_at' => now()->subMinutes(5),
            'download_progress' => 100,
        ]);
    }
}
