<?php

namespace Database\Factories;

use App\Models\Alarm;
use App\Models\CpeDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlarmFactory extends Factory
{
    protected $model = Alarm::class;

    public function definition(): array
    {
        $severities = ['critical', 'major', 'minor', 'warning', 'info'];
        $statuses = ['active', 'acknowledged', 'cleared'];
        $categories = ['connectivity', 'firmware', 'diagnostics'];
        $alarmTypes = ['device_offline', 'firmware_failed', 'diagnostic_failed', 'system', 'configuration'];

        return [
            'device_id' => null,
            'alarm_type' => $this->faker->randomElement($alarmTypes),
            'severity' => $this->faker->randomElement($severities),
            'status' => 'active',
            'category' => $this->faker->randomElement($categories),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->sentence(10),
            'metadata' => null,
            'raised_at' => now(),
            'acknowledged_at' => null,
            'acknowledged_by' => null,
            'cleared_at' => null,
            'resolution' => null,
        ];
    }

    public function critical(): self
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }

    public function major(): self
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'major',
        ]);
    }

    public function minor(): self
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'minor',
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function acknowledged(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);
    }

    public function cleared(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cleared',
            'cleared_at' => now(),
            'resolution' => $this->faker->sentence(8),
        ]);
    }

    public function forDevice(CpeDevice $device): self
    {
        return $this->state(fn (array $attributes) => [
            'device_id' => $device->id,
        ]);
    }

    public function deviceOffline(): self
    {
        return $this->state(fn (array $attributes) => [
            'alarm_type' => 'device_offline',
            'severity' => 'major',
            'category' => 'connectivity',
            'title' => 'Device Offline',
        ]);
    }

    public function firmwareFailed(): self
    {
        return $this->state(fn (array $attributes) => [
            'alarm_type' => 'firmware_failed',
            'severity' => 'major',
            'category' => 'firmware',
            'title' => 'Firmware Deployment Failed',
        ]);
    }

    public function diagnosticFailed(): self
    {
        return $this->state(fn (array $attributes) => [
            'alarm_type' => 'diagnostic_failed',
            'severity' => 'minor',
            'category' => 'diagnostics',
            'title' => 'Diagnostic Test Failed',
        ]);
    }
}
