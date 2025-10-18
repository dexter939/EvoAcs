<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\CpeDevice;
use App\Services\AlarmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlarmsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_alarms_index_page()
    {
        $response = $this->get(route('acs.alarms'));

        $response->assertStatus(200);
        $response->assertViewIs('acs.alarms.index');
        $response->assertViewHas('alarms');
        $response->assertViewHas('stats');
    }

    public function test_alarms_index_displays_active_alarms()
    {
        $alarm1 = Alarm::factory()->active()->create(['title' => 'Critical Test Alarm']);
        $alarm2 = Alarm::factory()->acknowledged()->create();
        $alarm3 = Alarm::factory()->cleared()->create();

        $response = $this->get(route('acs.alarms'));

        $response->assertSee('Critical Test Alarm');
        $response->assertSee($alarm2->title);
        $response->assertSee($alarm3->title);
    }

    public function test_can_filter_alarms_by_status()
    {
        $activeAlarm = Alarm::factory()->active()->create(['title' => 'Active Alarm']);
        $acknowledgedAlarm = Alarm::factory()->acknowledged()->create(['title' => 'Acknowledged Alarm']);

        $response = $this->get(route('acs.alarms', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertSee('Active Alarm');
    }

    public function test_can_filter_alarms_by_severity()
    {
        $criticalAlarm = Alarm::factory()->critical()->create(['title' => 'Critical Alarm']);
        $minorAlarm = Alarm::factory()->minor()->create(['title' => 'Minor Alarm']);

        $response = $this->get(route('acs.alarms', ['severity' => 'critical']));

        $response->assertStatus(200);
        $response->assertSee('Critical Alarm');
    }

    public function test_can_filter_alarms_by_category()
    {
        $connectivityAlarm = Alarm::factory()->create([
            'category' => 'connectivity',
            'title' => 'Connection Lost'
        ]);
        $firmwareAlarm = Alarm::factory()->create([
            'category' => 'firmware',
            'title' => 'Firmware Failed'
        ]);

        $response = $this->get(route('acs.alarms', ['category' => 'connectivity']));

        $response->assertStatus(200);
        $response->assertSee('Connection Lost');
    }

    public function test_can_acknowledge_active_alarm()
    {
        $alarm = Alarm::factory()->active()->create();

        $response = $this->postJson(route('acs.alarms.acknowledge', $alarm->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Alarm acknowledged successfully'
        ]);

        $this->assertDatabaseHas('alarms', [
            'id' => $alarm->id,
            'status' => 'acknowledged'
        ]);
    }

    public function test_cannot_acknowledge_already_acknowledged_alarm()
    {
        $alarm = Alarm::factory()->acknowledged()->create();

        $response = $this->postJson(route('acs.alarms.acknowledge', $alarm->id));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Alarm not found or already acknowledged'
        ]);
    }

    public function test_cannot_acknowledge_cleared_alarm()
    {
        $alarm = Alarm::factory()->cleared()->create();

        $response = $this->postJson(route('acs.alarms.acknowledge', $alarm->id));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false
        ]);
    }

    public function test_cannot_acknowledge_nonexistent_alarm()
    {
        $response = $this->postJson(route('acs.alarms.acknowledge', 99999));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false
        ]);
    }

    public function test_can_clear_active_alarm()
    {
        $alarm = Alarm::factory()->active()->create();
        $resolution = 'Fixed after router reboot';

        $response = $this->postJson(route('acs.alarms.clear', $alarm->id), [
            'resolution' => $resolution
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Alarm cleared successfully'
        ]);

        $this->assertDatabaseHas('alarms', [
            'id' => $alarm->id,
            'status' => 'cleared',
            'resolution' => $resolution
        ]);
    }

    public function test_can_clear_acknowledged_alarm()
    {
        $alarm = Alarm::factory()->acknowledged()->create();

        $response = $this->postJson(route('acs.alarms.clear', $alarm->id), [
            'resolution' => 'Resolved'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $this->assertDatabaseHas('alarms', [
            'id' => $alarm->id,
            'status' => 'cleared'
        ]);
    }

    public function test_cannot_clear_already_cleared_alarm()
    {
        $alarm = Alarm::factory()->cleared()->create();

        $response = $this->postJson(route('acs.alarms.clear', $alarm->id));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Alarm not found or already cleared'
        ]);
    }

    public function test_clear_alarm_validates_resolution_length()
    {
        $alarm = Alarm::factory()->active()->create();
        $longResolution = str_repeat('a', 501);

        $response = $this->postJson(route('acs.alarms.clear', $alarm->id), [
            'resolution' => $longResolution
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('resolution');
    }

    public function test_can_get_alarm_stats()
    {
        Alarm::factory()->active()->critical()->create();
        Alarm::factory()->active()->major()->create();
        Alarm::factory()->active()->minor()->create();
        Alarm::factory()->acknowledged()->create();

        $response = $this->getJson(route('acs.alarms.stats'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_active',
                'critical',
                'major',
                'minor',
                'warning',
                'info',
                'by_category'
            ]
        ]);

        $data = $response->json('data');
        $this->assertEquals(3, $data['total_active']);
        $this->assertEquals(1, $data['critical']);
        $this->assertEquals(1, $data['major']);
        $this->assertEquals(1, $data['minor']);
    }

    public function test_sse_stream_endpoint_returns_correct_headers()
    {
        $response = $this->get(route('acs.alarms.stream'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream');
        $response->assertHeader('Cache-Control', 'no-cache');
        $response->assertHeader('X-Accel-Buffering', 'no');
    }

    public function test_alarms_are_paginated()
    {
        Alarm::factory()->count(60)->create();

        $response = $this->get(route('acs.alarms'));

        $response->assertStatus(200);
        $response->assertViewHas('alarms');
        
        $alarms = $response->viewData('alarms');
        $this->assertEquals(50, $alarms->perPage());
        $this->assertLessThanOrEqual(50, $alarms->count());
    }

    public function test_alarms_display_device_relationship()
    {
        $device = CpeDevice::factory()->create(['serial_number' => 'TEST-DEVICE-123']);
        $alarm = Alarm::factory()->forDevice($device)->create([
            'title' => 'Device Offline Test'
        ]);

        $response = $this->get(route('acs.alarms'));

        $response->assertStatus(200);
        $response->assertSee('Device Offline Test');
    }

    public function test_stats_cards_display_correct_counts()
    {
        Alarm::factory()->active()->critical()->count(3)->create();
        Alarm::factory()->active()->major()->count(2)->create();
        Alarm::factory()->active()->minor()->count(1)->create();
        Alarm::factory()->acknowledged()->create();

        $response = $this->get(route('acs.alarms'));

        $stats = $response->viewData('stats');
        $this->assertEquals(6, $stats['total_active']);
        $this->assertEquals(3, $stats['critical']);
        $this->assertEquals(2, $stats['major']);
        $this->assertEquals(1, $stats['minor']);
    }
}
