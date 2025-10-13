<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Models\DiagnosticTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class DiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake(); // Prevent jobs from actually executing in tests
    }

    public function test_ping_diagnostic_creates_task(): void
    {
        $device = CpeDevice::factory()->online()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/diagnostics/ping", [
            'host' => '8.8.8.8',
            'number_of_repetitions' => 4,
            'timeout' => 1000,
            'data_block_size' => 64
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'diagnostic_id',
                    'status',
                    'test_type'
                ]
            ]);

        $this->assertDatabaseHas('diagnostic_tests', [
            'cpe_device_id' => $device->id,
            'diagnostic_type' => 'IPPing',
            'status' => 'pending'
        ]);
    }

    public function test_ping_validates_required_fields(): void
    {
        $device = CpeDevice::factory()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/diagnostics/ping", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['host']);
    }

    public function test_traceroute_diagnostic_creates_task(): void
    {
        $device = CpeDevice::factory()->online()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/diagnostics/traceroute", [
            'host' => 'www.google.com',
            'number_of_tries' => 3,
            'timeout' => 5000,
            'max_hop_count' => 30
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'diagnostic_id',
                    'status'
                ]
            ]);

        $this->assertDatabaseHas('diagnostic_tests', [
            'cpe_device_id' => $device->id,
            'diagnostic_type' => 'TraceRoute'
        ]);
    }

    public function test_download_diagnostic_creates_speed_test(): void
    {
        $device = CpeDevice::factory()->online()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/diagnostics/download", [
            'download_url' => 'http://speedtest.example.com/10MB.bin',
            'test_file_length' => 10485760
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'diagnostic_id',
                    'status',
                    'test_type'
                ]
            ]);

        $this->assertDatabaseHas('diagnostic_tests', [
            'cpe_device_id' => $device->id,
            'diagnostic_type' => 'DownloadDiagnostics'
        ]);
    }

    public function test_upload_diagnostic_creates_speed_test(): void
    {
        $device = CpeDevice::factory()->online()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/diagnostics/upload", [
            'upload_url' => 'http://speedtest.example.com/upload',
            'test_file_length' => 1048576
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'diagnostic_id',
                    'status'
                ]
            ]);

        $this->assertDatabaseHas('diagnostic_tests', [
            'cpe_device_id' => $device->id,
            'diagnostic_type' => 'UploadDiagnostics'
        ]);
    }

    public function test_list_all_diagnostics(): void
    {
        $device = CpeDevice::factory()->create();
        
        DiagnosticTest::factory()->count(5)->create([
            'cpe_device_id' => $device->id
        ]);

        $response = $this->apiGet('/api/v1/diagnostics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'test_type',
                        'status',
                        'cpe_device_id',
                        'created_at'
                    ]
                ]
            ]);
    }

    public function test_list_device_diagnostics(): void
    {
        $device1 = CpeDevice::factory()->create();
        $device2 = CpeDevice::factory()->create();
        
        DiagnosticTest::factory()->count(3)->create(['cpe_device_id' => $device1->id]);
        DiagnosticTest::factory()->count(2)->create(['cpe_device_id' => $device2->id]);

        $response = $this->apiGet("/api/v1/devices/{$device1->id}/diagnostics");

        $response->assertStatus(200);
        
        $diagnostics = $response->json('data');
        $this->assertCount(3, $diagnostics);
        
        foreach ($diagnostics as $diagnostic) {
            $this->assertEquals($device1->id, $diagnostic['cpe_device_id']);
        }
    }

    public function test_get_diagnostic_results(): void
    {
        $device = CpeDevice::factory()->create();
        
        $diagnostic = DiagnosticTest::factory()->create([
            'cpe_device_id' => $device->id,
            'diagnostic_type' => 'IPPing',
            'status' => 'completed',
            'results' => [
                'success_count' => 4,
                'failure_count' => 0,
                'average_response_time' => 25,
                'min_response_time' => 20,
                'max_response_time' => 30
            ]
        ]);

        $response = $this->apiGet("/api/v1/diagnostics/{$diagnostic->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'test_type',
                    'status',
                    'result' => [
                        'success_count',
                        'failure_count',
                        'average_response_time'
                    ]
                ]
            ]);
    }

    public function test_diagnostics_require_online_device(): void
    {
        $device = CpeDevice::factory()->offline()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/diagnostics/ping", [
            'host' => '8.8.8.8'
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Device must be online to run diagnostics'
            ]);
    }

    public function test_filter_diagnostics_by_status(): void
    {
        DiagnosticTest::factory()->count(3)->create(['status' => 'completed']);
        DiagnosticTest::factory()->count(2)->create(['status' => 'pending']);

        $response = $this->apiGet('/api/v1/diagnostics?status=completed');

        $response->assertStatus(200);
        
        $diagnostics = $response->json('data');
        $this->assertCount(3, $diagnostics);
        
        foreach ($diagnostics as $diagnostic) {
            $this->assertEquals('completed', $diagnostic['status']);
        }
    }
}
