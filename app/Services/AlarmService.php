<?php

namespace App\Services;

use App\Events\AlarmCreated;
use App\Models\Alarm;
use App\Models\CpeDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AlarmService
{
    public function raiseAlarm(array $data): Alarm
    {
        // Check for existing active alarm of same type for same device
        $existingAlarm = Alarm::where('device_id', $data['device_id'] ?? null)
            ->where('alarm_type', $data['alarm_type'])
            ->where('status', 'active')
            ->first();

        if ($existingAlarm) {
            Log::info("Alarm already exists, skipping duplicate: {$existingAlarm->title}", [
                'alarm_id' => $existingAlarm->id,
                'alarm_type' => $data['alarm_type'],
                'device_id' => $data['device_id'] ?? null,
            ]);
            return $existingAlarm;
        }

        $alarm = Alarm::create([
            'device_id' => $data['device_id'] ?? null,
            'alarm_type' => $data['alarm_type'],
            'severity' => $data['severity'] ?? 'info',
            'status' => 'active',
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'raised_at' => now(),
        ]);

        Log::info("Alarm raised: {$alarm->title}", [
            'alarm_id' => $alarm->id,
            'severity' => $alarm->severity,
            'device_id' => $alarm->device_id,
        ]);

        event(new AlarmCreated($alarm));

        return $alarm;
    }

    public function acknowledgeAlarm(int $alarmId, ?int $userId = null): ?Alarm
    {
        $alarm = Alarm::find($alarmId);
        
        if (!$alarm || $alarm->status !== 'active') {
            return null;
        }

        $alarm->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId ?? Auth::id(),
        ]);

        Log::info("Alarm acknowledged: {$alarm->title}", [
            'alarm_id' => $alarm->id,
            'acknowledged_by' => $alarm->acknowledged_by,
        ]);

        return $alarm->fresh();
    }

    public function clearAlarm(int $alarmId, ?string $resolution = null): ?Alarm
    {
        $alarm = Alarm::find($alarmId);
        
        if (!$alarm || $alarm->status === 'cleared') {
            return null;
        }

        $alarm->update([
            'status' => 'cleared',
            'cleared_at' => now(),
            'resolution' => $resolution,
        ]);

        Log::info("Alarm cleared: {$alarm->title}", [
            'alarm_id' => $alarm->id,
            'resolution' => $resolution,
        ]);

        return $alarm->fresh();
    }

    public function getActiveAlarms(?int $deviceId = null): Collection
    {
        $query = Alarm::where('status', 'active')
            ->orderBy('severity', 'desc')
            ->orderBy('raised_at', 'desc');

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        return $query->get();
    }

    public function getAlarmsByDevice(int $deviceId, ?string $status = null): Collection
    {
        $query = Alarm::where('device_id', $deviceId)
            ->orderBy('raised_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function getAlarmStats(): array
    {
        return [
            'total_active' => Alarm::where('status', 'active')->count(),
            'critical' => Alarm::where('status', 'active')->where('severity', 'critical')->count(),
            'major' => Alarm::where('status', 'active')->where('severity', 'major')->count(),
            'minor' => Alarm::where('status', 'active')->where('severity', 'minor')->count(),
            'warning' => Alarm::where('status', 'active')->where('severity', 'warning')->count(),
            'info' => Alarm::where('status', 'active')->where('severity', 'info')->count(),
            'by_category' => Alarm::where('status', 'active')
                ->groupBy('category')
                ->selectRaw('category, count(*) as count')
                ->pluck('count', 'category')
                ->toArray(),
            'trends_24h' => $this->getAlarmTrends24h(),
        ];
    }
    
    public function getAlarmTrends24h(): array
    {
        $hoursAgo = 24;
        $hourlyData = [];
        
        for ($i = $hoursAgo - 1; $i >= 0; $i--) {
            $startHour = now()->subHours($i + 1);
            $endHour = now()->subHours($i);
            
            $hourlyData[] = [
                'hour' => $endHour->format('H:i'),
                'total' => Alarm::whereBetween('raised_at', [$startHour, $endHour])->count(),
                'critical' => Alarm::whereBetween('raised_at', [$startHour, $endHour])
                    ->where('severity', 'critical')->count(),
                'major' => Alarm::whereBetween('raised_at', [$startHour, $endHour])
                    ->where('severity', 'major')->count(),
                'minor' => Alarm::whereBetween('raised_at', [$startHour, $endHour])
                    ->whereIn('severity', ['minor', 'warning', 'info'])->count(),
            ];
        }
        
        return [
            'labels' => array_column($hourlyData, 'hour'),
            'total' => array_column($hourlyData, 'total'),
            'critical' => array_column($hourlyData, 'critical'),
            'major' => array_column($hourlyData, 'major'),
            'minor' => array_column($hourlyData, 'minor'),
        ];
    }

    public function autoRaiseDeviceOfflineAlarm(CpeDevice $device): ?Alarm
    {
        $existingAlarm = Alarm::where('device_id', $device->id)
            ->where('alarm_type', 'device_offline')
            ->where('status', 'active')
            ->first();

        if ($existingAlarm) {
            return null;
        }

        return $this->raiseAlarm([
            'device_id' => $device->id,
            'alarm_type' => 'device_offline',
            'severity' => 'major',
            'category' => 'connectivity',
            'title' => "Device Offline: {$device->serial_number}",
            'description' => "Device {$device->serial_number} ({$device->manufacturer} {$device->model_name}) has gone offline. Last seen: " . ($device->last_inform ? $device->last_inform->diffForHumans() : 'never'),
            'metadata' => [
                'serial_number' => $device->serial_number,
                'manufacturer' => $device->manufacturer,
                'model' => $device->model_name,
                'last_inform' => $device->last_inform?->toIso8601String(),
            ],
        ]);
    }

    public function autoClearDeviceOfflineAlarm(CpeDevice $device): void
    {
        Alarm::where('device_id', $device->id)
            ->where('alarm_type', 'device_offline')
            ->where('status', 'active')
            ->update([
                'status' => 'cleared',
                'cleared_at' => now(),
                'resolution' => 'Device came back online',
            ]);
    }
}
