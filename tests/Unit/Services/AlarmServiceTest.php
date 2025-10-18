<?php

namespace Tests\Unit\Services;

use App\Events\AlarmCreated;
use App\Models\Alarm;
use App\Models\CpeDevice;
use App\Services\AlarmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AlarmServiceTest extends TestCase
{
    use RefreshDatabase;

    private AlarmService $alarmService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alarmService = app(AlarmService::class);
    }

    public function test_can_raise_alarm_without_device()
    {
        Event::fake();

        $alarm = $this->alarmService->raiseAlarm([
            'alarm_type' => 'system',
            'severity' => 'critical',
            'category' => 'connectivity',
            'title' => 'System Critical Error',
            'description' => 'Critical system failure detected',
        ]);

        $this->assertInstanceOf(Alarm::class, $alarm);
        $this->assertEquals('system', $alarm->alarm_type);
        $this->assertEquals('critical', $alarm->severity);
        $this->assertEquals('active', $alarm->status);
        $this->assertEquals('connectivity', $alarm->category);
        $this->assertEquals('System Critical Error', $alarm->title);
        $this->assertDatabaseHas('alarms', [
            'id' => $alarm->id,
            'status' => 'active',
        ]);

        Event::assertDispatched(AlarmCreated::class, function ($event) use ($alarm) {
            return $event->alarm->id === $alarm->id;
        });
    }

    public function test_can_raise_alarm_with_device()
    {
        $device = CpeDevice::factory()->create();

        $alarm = $this->alarmService->raiseAlarm([
            'device_id' => $device->id,
            'alarm_type' => 'device_offline',
            'severity' => 'major',
            'category' => 'connectivity',
            'title' => 'Device Offline',
            'description' => 'Device not responding',
        ]);

        $this->assertEquals($device->id, $alarm->device_id);
        $this->assertDatabaseHas('alarms', [
            'device_id' => $device->id,
            'alarm_type' => 'device_offline',
        ]);
    }

    public function test_can_raise_alarm_with_metadata()
    {
        $metadata = [
            'serial_number' => '12345',
            'manufacturer' => 'MikroTik',
            'model' => 'RB4011',
        ];

        $alarm = $this->alarmService->raiseAlarm([
            'alarm_type' => 'test',
            'severity' => 'info',
            'category' => 'diagnostics',
            'title' => 'Test Alarm',
            'metadata' => $metadata,
        ]);

        $this->assertEquals($metadata, $alarm->metadata);
    }

    public function test_can_acknowledge_active_alarm()
    {
        $alarm = Alarm::factory()->create(['status' => 'active']);

        $acknowledged = $this->alarmService->acknowledgeAlarm($alarm->id);

        $this->assertNotNull($acknowledged);
        $this->assertEquals('acknowledged', $acknowledged->status);
        $this->assertNotNull($acknowledged->acknowledged_at);
        $this->assertDatabaseHas('alarms', [
            'id' => $alarm->id,
            'status' => 'acknowledged',
        ]);
    }

    public function test_cannot_acknowledge_non_active_alarm()
    {
        $alarm = Alarm::factory()->create(['status' => 'acknowledged']);

        $result = $this->alarmService->acknowledgeAlarm($alarm->id);

        $this->assertNull($result);
    }

    public function test_cannot_acknowledge_nonexistent_alarm()
    {
        $result = $this->alarmService->acknowledgeAlarm(99999);

        $this->assertNull($result);
    }

    public function test_can_clear_active_alarm()
    {
        $alarm = Alarm::factory()->create(['status' => 'active']);
        $resolution = 'Fixed after reboot';

        $cleared = $this->alarmService->clearAlarm($alarm->id, $resolution);

        $this->assertNotNull($cleared);
        $this->assertEquals('cleared', $cleared->status);
        $this->assertEquals($resolution, $cleared->resolution);
        $this->assertNotNull($cleared->cleared_at);
        $this->assertDatabaseHas('alarms', [
            'id' => $alarm->id,
            'status' => 'cleared',
            'resolution' => $resolution,
        ]);
    }

    public function test_can_clear_acknowledged_alarm()
    {
        $alarm = Alarm::factory()->create(['status' => 'acknowledged']);

        $cleared = $this->alarmService->clearAlarm($alarm->id, 'Resolved');

        $this->assertNotNull($cleared);
        $this->assertEquals('cleared', $cleared->status);
    }

    public function test_cannot_clear_already_cleared_alarm()
    {
        $alarm = Alarm::factory()->create(['status' => 'cleared']);

        $result = $this->alarmService->clearAlarm($alarm->id);

        $this->assertNull($result);
    }

    public function test_can_get_active_alarms()
    {
        Alarm::factory()->create(['status' => 'active', 'severity' => 'critical']);
        Alarm::factory()->create(['status' => 'active', 'severity' => 'major']);
        Alarm::factory()->create(['status' => 'acknowledged']);
        Alarm::factory()->create(['status' => 'cleared']);

        $activeAlarms = $this->alarmService->getActiveAlarms();

        $this->assertCount(2, $activeAlarms);
        $this->assertTrue($activeAlarms->every(fn($alarm) => $alarm->status === 'active'));
    }

    public function test_can_get_active_alarms_by_device()
    {
        $device1 = CpeDevice::factory()->create();
        $device2 = CpeDevice::factory()->create();

        Alarm::factory()->create(['device_id' => $device1->id, 'status' => 'active']);
        Alarm::factory()->create(['device_id' => $device1->id, 'status' => 'active']);
        Alarm::factory()->create(['device_id' => $device2->id, 'status' => 'active']);

        $device1Alarms = $this->alarmService->getActiveAlarms($device1->id);

        $this->assertCount(2, $device1Alarms);
        $this->assertTrue($device1Alarms->every(fn($alarm) => $alarm->device_id === $device1->id));
    }

    public function test_can_get_alarms_by_device_with_status_filter()
    {
        $device = CpeDevice::factory()->create();

        Alarm::factory()->create(['device_id' => $device->id, 'status' => 'active']);
        Alarm::factory()->create(['device_id' => $device->id, 'status' => 'acknowledged']);
        Alarm::factory()->create(['device_id' => $device->id, 'status' => 'cleared']);

        $acknowledgedAlarms = $this->alarmService->getAlarmsByDevice($device->id, 'acknowledged');

        $this->assertCount(1, $acknowledgedAlarms);
        $this->assertEquals('acknowledged', $acknowledgedAlarms->first()->status);
    }

    public function test_can_get_alarm_stats()
    {
        Alarm::factory()->create(['status' => 'active', 'severity' => 'critical', 'category' => 'connectivity']);
        Alarm::factory()->create(['status' => 'active', 'severity' => 'critical', 'category' => 'firmware']);
        Alarm::factory()->create(['status' => 'active', 'severity' => 'major', 'category' => 'connectivity']);
        Alarm::factory()->create(['status' => 'active', 'severity' => 'minor', 'category' => 'diagnostics']);
        Alarm::factory()->create(['status' => 'acknowledged', 'severity' => 'critical']);
        Alarm::factory()->create(['status' => 'cleared']);

        $stats = $this->alarmService->getAlarmStats();

        $this->assertEquals(4, $stats['total_active']);
        $this->assertEquals(2, $stats['critical']);
        $this->assertEquals(1, $stats['major']);
        $this->assertEquals(1, $stats['minor']);
        $this->assertEquals(2, $stats['by_category']['connectivity']);
        $this->assertEquals(1, $stats['by_category']['firmware']);
        $this->assertEquals(1, $stats['by_category']['diagnostics']);
    }

    public function test_can_auto_raise_device_offline_alarm()
    {
        $device = CpeDevice::factory()->create([
            'status' => 'offline',
            'last_inform' => now()->subHours(2),
        ]);

        $alarm = $this->alarmService->autoRaiseDeviceOfflineAlarm($device);

        $this->assertNotNull($alarm);
        $this->assertEquals($device->id, $alarm->device_id);
        $this->assertEquals('device_offline', $alarm->alarm_type);
        $this->assertEquals('major', $alarm->severity);
        $this->assertEquals('connectivity', $alarm->category);
        $this->assertStringContainsString($device->serial_number, $alarm->title);
    }

    public function test_cannot_duplicate_device_offline_alarm()
    {
        $device = CpeDevice::factory()->create(['status' => 'offline']);

        $alarm1 = $this->alarmService->autoRaiseDeviceOfflineAlarm($device);
        $alarm2 = $this->alarmService->autoRaiseDeviceOfflineAlarm($device);

        $this->assertNotNull($alarm1);
        $this->assertNull($alarm2);
        $this->assertEquals(1, Alarm::where('device_id', $device->id)
            ->where('alarm_type', 'device_offline')
            ->where('status', 'active')
            ->count());
    }

    public function test_can_auto_clear_device_offline_alarm()
    {
        $device = CpeDevice::factory()->create();
        $alarm = Alarm::factory()->create([
            'device_id' => $device->id,
            'alarm_type' => 'device_offline',
            'status' => 'active',
        ]);

        $this->alarmService->autoClearDeviceOfflineAlarm($device);

        $this->assertDatabaseHas('alarms', [
            'id' => $alarm->id,
            'status' => 'cleared',
            'resolution' => 'Device came back online',
        ]);
    }

    public function test_auto_clear_only_affects_device_offline_alarms()
    {
        $device = CpeDevice::factory()->create();
        $offlineAlarm = Alarm::factory()->create([
            'device_id' => $device->id,
            'alarm_type' => 'device_offline',
            'status' => 'active',
        ]);
        $otherAlarm = Alarm::factory()->create([
            'device_id' => $device->id,
            'alarm_type' => 'firmware_failed',
            'status' => 'active',
        ]);

        $this->alarmService->autoClearDeviceOfflineAlarm($device);

        $this->assertDatabaseHas('alarms', [
            'id' => $offlineAlarm->id,
            'status' => 'cleared',
        ]);
        $this->assertDatabaseHas('alarms', [
            'id' => $otherAlarm->id,
            'status' => 'active',
        ]);
    }
}
