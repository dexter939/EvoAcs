<?php

namespace Database\Factories;

use App\Models\CpeDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

class CpeDeviceFactory extends Factory
{
    protected $model = CpeDevice::class;

    public function definition(): array
    {
        return [
            'serial_number' => 'SN-' . strtoupper($this->faker->bothify('??########')),
            'oui' => $this->faker->randomElement(['00259E', 'F8C003', 'A4567B']),
            'product_class' => $this->faker->randomElement(['IGD', 'STB', 'VoIP']),
            'manufacturer' => $this->faker->randomElement(['Technicolor', 'Huawei', 'ZTE', 'TP-Link']),
            'model_name' => $this->faker->bothify('Model-???-###'),
            'hardware_version' => $this->faker->bothify('HW-#.#'),
            'software_version' => $this->faker->bothify('SW-#.#.#'),
            'protocol_type' => $this->faker->randomElement(['tr069', 'tr369']),
            'connection_request_url' => $this->faker->url(),
            'connection_request_username' => $this->faker->userName(),
            'connection_request_password' => bcrypt('password'),
            'status' => $this->faker->randomElement(['online', 'offline', 'pending']),
            'last_inform' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'ip_address' => $this->faker->ipv4(),
            'mac_address' => $this->faker->macAddress(),
            'mtp_type' => $this->faker->randomElement(['http', 'mqtt', 'websocket']),
        ];
    }

    public function tr069(): static
    {
        return $this->state(fn (array $attributes) => [
            'protocol_type' => 'tr069',
            'mtp_type' => null,
        ]);
    }

    public function tr369(): static
    {
        return $this->state(fn (array $attributes) => [
            'protocol_type' => 'tr369',
            'mtp_type' => $this->faker->randomElement(['http', 'mqtt', 'websocket']),
            'usp_endpoint_id' => 'proto::' . $this->faker->uuid(),
        ]);
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'last_inform' => now(),
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offline',
            'last_inform' => now()->subHours(24),
        ]);
    }
}
