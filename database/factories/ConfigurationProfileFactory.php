<?php

namespace Database\Factories;

use App\Models\ConfigurationProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConfigurationProfileFactory extends Factory
{
    protected $model = ConfigurationProfile::class;

    public function definition(): array
    {
        return [
            'name' => 'Profile ' . $this->faker->word() . ' ' . $this->faker->randomNumber(3),
            'description' => $this->faker->sentence(),
            'parameters' => [
                'Device.ManagementServer.PeriodicInformEnable' => '1',
                'Device.ManagementServer.PeriodicInformInterval' => '300',
                'Device.WiFi.SSID.1.SSID' => $this->faker->word() . '_' . $this->faker->randomNumber(4),
                'Device.WiFi.AccessPoint.1.Security.ModeEnabled' => 'WPA2-Personal',
            ],
            'is_active' => $this->faker->boolean(80),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
