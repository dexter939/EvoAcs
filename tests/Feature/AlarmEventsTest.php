<?php

namespace Tests\Feature;

use App\Events\DeviceWentOffline;
use App\Listeners\RaiseDeviceOfflineAlarm;
use App\Models\Alarm;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AlarmEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_went_offline_event_is_dispatched()
    {
        Event::fake();
        $device = CpeDevice::factory()->create(['status' => 'offline']);

        event(new DeviceWentOffline($device));

        Event::assertDispatched(DeviceWentOffline::class);
    }

    public function test_device_offline_listener_creates_alarm()
    {
        $device = CpeDevice::factory()->create(['status' => 'offline']);
        $event = new DeviceWentOffline($device);
        $listener = app(RaiseDeviceOfflineAlarm::class);

        $listener->handle($event);

        $this->assertDatabaseHas('alarms', [
            'device_id' => $device->id,
            'alarm_type' => 'device_offline',
            'severity' => 'major',
            'category' => 'connectivity',
        ]);
    }

    public function test_device_offline_listener_does_not_duplicate_alarms()
    {
        $device = CpeDevice::factory()->create(['status' => 'offline']);

        Alarm::factory()->create([
            'device_id' => $device->id,
            'alarm_type' => 'device_offline',
            'status' => 'active',
        ]);

        $event = new DeviceWentOffline($device);
        $listener = app(RaiseDeviceOfflineAlarm::class);

        $listener->handle($event);

        $this->assertEquals(1, Alarm::where('device_id', $device->id)
            ->where('alarm_type', 'device_offline')
            ->where('status', 'active')
            ->count());
    }

    public function test_device_offline_event_listener_is_registered()
    {
        $listeners = Event::getListeners(DeviceWentOffline::class);
        $this->assertNotEmpty($listeners);
    }
}
