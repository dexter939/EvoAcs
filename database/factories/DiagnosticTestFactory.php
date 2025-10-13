<?php

namespace Database\Factories;

use App\Models\CpeDevice;
use App\Models\DiagnosticTest;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiagnosticTestFactory extends Factory
{
    protected $model = DiagnosticTest::class;

    public function definition(): array
    {
        return [
            'cpe_device_id' => CpeDevice::factory(),
            'diagnostic_type' => $this->faker->randomElement(['IPPing', 'TraceRoute', 'DownloadDiagnostics', 'UploadDiagnostics']),
            'status' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'parameters' => [
                'host' => '8.8.8.8',
                'timeout' => 1000,
                'number_of_repetitions' => 4
            ],
            'results' => null,
            'command_key' => 'DIAG_' . $this->faker->uuid(),
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => null,
            'error_message' => null,
        ];
    }

    public function ping(): static
    {
        return $this->state(fn (array $attributes) => [
            'diagnostic_type' => 'IPPing',
            'parameters' => [
                'host' => '8.8.8.8',
                'timeout' => 1000,
                'number_of_repetitions' => 4,
                'data_block_size' => 64
            ],
        ]);
    }

    public function traceroute(): static
    {
        return $this->state(fn (array $attributes) => [
            'diagnostic_type' => 'TraceRoute',
            'parameters' => [
                'host' => '8.8.8.8',
                'timeout' => 5000,
                'number_of_tries' => 3,
                'max_hop_count' => 30
            ],
        ]);
    }

    public function download(): static
    {
        return $this->state(fn (array $attributes) => [
            'diagnostic_type' => 'DownloadDiagnostics',
            'parameters' => [
                'download_url' => 'http://speedtest.example.com/download',
                'test_file_length' => 10485760
            ],
        ]);
    }

    public function upload(): static
    {
        return $this->state(fn (array $attributes) => [
            'diagnostic_type' => 'UploadDiagnostics',
            'parameters' => [
                'upload_url' => 'http://speedtest.example.com/upload',
                'test_file_length' => 1048576
            ],
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
            'results' => [
                'success_count' => 4,
                'failure_count' => 0,
                'average_response_time' => 25,
            ],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => 'Diagnostic test failed',
        ]);
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now(),
        ]);
    }
}
