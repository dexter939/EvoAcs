<?php

namespace App\Http\Controllers;

use App\Events\AlarmCreated;
use App\Models\Alarm;
use App\Services\AlarmService;
use Illuminate\Http\Request;

class AlarmsController extends Controller
{
    public function __construct(
        private AlarmService $alarmService
    ) {}

    public function index(Request $request)
    {
        $query = Alarm::with(['device', 'acknowledgedBy'])
            ->orderBy('raised_at', 'desc');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $alarms = $query->paginate(50);
        $stats = $this->alarmService->getAlarmStats();

        return view('acs.alarms.index', compact('alarms', 'stats'));
    }

    public function acknowledge(Request $request, int $id)
    {
        $alarm = $this->alarmService->acknowledgeAlarm($id);

        if (!$alarm) {
            return response()->json([
                'success' => false,
                'message' => 'Alarm not found or already acknowledged'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Alarm acknowledged successfully',
            'data' => $alarm
        ]);
    }

    public function clear(Request $request, int $id)
    {
        $request->validate([
            'resolution' => 'nullable|string|max:500'
        ]);

        $alarm = $this->alarmService->clearAlarm($id, $request->resolution);

        if (!$alarm) {
            return response()->json([
                'success' => false,
                'message' => 'Alarm not found or already cleared'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Alarm cleared successfully',
            'data' => $alarm
        ]);
    }

    public function getStats()
    {
        $stats = $this->alarmService->getAlarmStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function stream(Request $request)
    {
        return response()->stream(function () {
            set_time_limit(0);
            ignore_user_abort(true);
            
            $lastId = request('lastId', 0);
            $heartbeatCounter = 0;
            
            while (true) {
                $newAlarms = Alarm::where('id', '>', $lastId)
                    ->with(['device'])
                    ->orderBy('id', 'asc')
                    ->get();
                
                if ($newAlarms->isNotEmpty()) {
                    foreach ($newAlarms as $alarm) {
                        $data = [
                            'id' => $alarm->id,
                            'title' => $alarm->title,
                            'severity' => $alarm->severity,
                            'category' => $alarm->category,
                            'device_name' => $alarm->device?->hostname ?? 'Unknown',
                            'raised_at' => $alarm->raised_at->diffForHumans(),
                        ];
                        
                        echo "data: " . json_encode($data) . "\n\n";
                        
                        $lastId = max($lastId, $alarm->id);
                    }
                    
                    $heartbeatCounter = 0;
                    ob_flush();
                    flush();
                }
                
                $heartbeatCounter++;
                if ($heartbeatCounter >= 15) {
                    echo ": heartbeat\n\n";
                    $heartbeatCounter = 0;
                    ob_flush();
                    flush();
                }
                
                sleep(2);
                
                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
