<?php

namespace Tests\Feature;

use App\Events\DeviceWentOffline;
use App\Events\FirmwareDeploymentFailed;
use App\Events\DiagnosticTestFailed;
use App\Listeners\RaiseDeviceOfflineAlarm;
use App\Listeners\RaiseFirmwareFailureAlarm;
use App\Listeners\RaiseDiagnosticFailureAlarm;
use App\Models\Alarm;
use App\Models\CpeDevice;
use App\Models\FirmwareDeployment;
use App\Models\DiagnosticTest;
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

    public function test_firmware_deployment_failed_event_is_dispatched()
    {
        Event::fake();
        $deployment = FirmwareDeployment::factory()->failed()->create();

        event(new FirmwareDeploymentFailed($deployment, 'Download timeout'));

        Event::assertDispatched(FirmwareDeploymentFailed::class);
    }

    public function test_firmware_failure_listener_creates_alarm()
    {
        $deployment = FirmwareDeployment::factory()->failed()->create();
        $event = new FirmwareDeploymentFailed($deployment, 'Download failed');
        $listener = app(RaiseFirmwareFailureAlarm::class);

        $listener->handle($event);

        $this->assertDatabaseHas('alarms', [
            'device_id' => $deployment->cpe_device_id,
            'alarm_type' => 'firmware_deployment_failed',
            'severity' => 'major',
            'category' => 'firmware',
        ]);
    }

    public function test_firmware_failure_listener_does_not_duplicate_alarms()
    {
        $deployment = FirmwareDeployment::factory()->failed()->create();

        Alarm::factory()->create([
            'device_id' => $deployment->cpe_device_id,
            'alarm_type' => 'firmware_deployment_failed',
            'status' => 'active',
        ]);

        $event = new FirmwareDeploymentFailed($deployment, 'Download failed');
        $listener = app(RaiseFirmwareFailureAlarm::class);

        $listener->handle($event);

        $this->assertEquals(1, Alarm::where('device_id', $deployment->cpe_device_id)
            ->where('alarm_type', 'firmware_deployment_failed')
            ->where('status', 'active')
            ->count());
    }

    public function test_firmware_failure_event_listener_is_registered()
    {
        $listeners = Event::getListeners(FirmwareDeploymentFailed::class);
        $this->assertNotEmpty($listeners);
    }

    public function test_diagnostic_test_failed_event_is_dispatched()
    {
        Event::fake();
        $test = DiagnosticTest::factory()->create(['status' => 'failed']);

        event(new DiagnosticTestFailed($test, 'Timeout'));

        Event::assertDispatched(DiagnosticTestFailed::class);
    }

    public function test_diagnostic_failure_listener_creates_alarm()
    {
        $test = DiagnosticTest::factory()->create([
            'diagnostic_type' => 'ping',
            'status' => 'failed',
        ]);
        $event = new DiagnosticTestFailed($test, 'Packet loss 100%');
        $listener = app(RaiseDiagnosticFailureAlarm::class);

        $listener->handle($event);

        $this->assertDatabaseHas('alarms', [
            'device_id' => $test->cpe_device_id,
            'alarm_type' => 'diagnostic_test_failed',
            'category' => 'diagnostics',
        ]);
    }

    public function test_diagnostic_failure_listener_severity_based_on_test_type()
    {
        $pingTest = DiagnosticTest::factory()->create(['diagnostic_type' => 'ping']);
        $downloadTest = DiagnosticTest::factory()->create(['diagnostic_type' => 'download_diagnostics']);

        $listener = app(RaiseDiagnosticFailureAlarm::class);
        $listener->handle(new DiagnosticTestFailed($pingTest, 'Failed'));
        $listener->handle(new DiagnosticTestFailed($downloadTest, 'Failed'));

        $pingAlarm = Alarm::where('device_id', $pingTest->cpe_device_id)->first();
        $downloadAlarm = Alarm::where('device_id', $downloadTest->cpe_device_id)->first();

        $this->assertEquals('minor', $pingAlarm->severity);
        $this->assertEquals('warning', $downloadAlarm->severity);
    }

    public function test_diagnostic_failure_listener_does_not_duplicate_alarms()
    {
        $test = DiagnosticTest::factory()->create(['status' => 'failed']);

        Alarm::factory()->create([
            'device_id' => $test->cpe_device_id,
            'alarm_type' => 'diagnostic_test_failed',
            'status' => 'active',
        ]);

        $event = new DiagnosticTestFailed($test, 'Test failed');
        $listener = app(RaiseDiagnosticFailureAlarm::class);

        $listener->handle($event);

        $this->assertEquals(1, Alarm::where('device_id', $test->cpe_device_id)
            ->where('alarm_type', 'diagnostic_test_failed')
            ->where('status', 'active')
            ->count());
    }

    public function test_diagnostic_failure_event_listener_is_registered()
    {
        $listeners = Event::getListeners(DiagnosticTestFailed::class);
        $this->assertNotEmpty($listeners);
    }
}
