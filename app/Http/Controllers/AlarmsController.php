<?php

namespace App\Http\Controllers;

use App\Events\AlarmCreated;
use App\Models\Alarm;
use App\Services\AlarmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AlarmsController extends Controller
{
    public function __construct(
        private AlarmService $alarmService
    ) {}

    public function index(Request $request)
    {
        $query = Alarm::with(['device', 'acknowledgedBy'])
            ->orderBy('raised_at', 'desc');

        // Default to "active" status if not specified
        $status = $request->input('status', 'active');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->has('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $alarms = $query->paginate(50)->appends($request->except('page'));
        $stats = $this->alarmService->getAlarmStats();

        return view('acs.alarms.index', compact('alarms', 'stats', 'status'));
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
            set_time_limit(300); // 5min max per connection (carrier-grade: rotate connections)
            ignore_user_abort(false); // Detect client disconnect properly
            
            $lastId = request('lastId', 0);
            $heartbeatCounter = 0;
            $maxIterations = 150; // Max 5 min at 2s intervals (carrier-grade: prevent runaway loops)
            $iteration = 0;
            
            while ($iteration < $maxIterations) {
                $iteration++;
                
                // Carrier-grade optimization: use index and limit query
                $newAlarms = Alarm::where('id', '>', $lastId)
                    ->with(['device:id,hostname,serial_number']) // Eager load only needed fields
                    ->orderBy('id', 'asc')
                    ->limit(10) // Batch size limit
                    ->get();
                
                if ($newAlarms->isNotEmpty()) {
                    foreach ($newAlarms as $alarm) {
                        $data = [
                            'id' => $alarm->id,
                            'title' => $alarm->title,
                            'severity' => $alarm->severity,
                            'category' => $alarm->category,
                            'device_name' => $alarm->device?->serial_number ?? 'System',
                            'raised_at' => $alarm->raised_at->diffForHumans(),
                        ];
                        
                        echo "data: " . json_encode($data) . "\n\n";
                        
                        $lastId = max($lastId, $alarm->id);
                    }
                    
                    $heartbeatCounter = 0;
                    @ob_flush();
                    @flush();
                }
                
                // Heartbeat every 30 seconds (reduced network overhead)
                $heartbeatCounter++;
                if ($heartbeatCounter >= 15) {
                    echo ": heartbeat\n\n";
                    $heartbeatCounter = 0;
                    @ob_flush();
                    @flush();
                }
                
                // Check connection before sleep (faster disconnect detection)
                if (connection_aborted()) {
                    Log::info('SSE connection aborted', ['lastId' => $lastId]);
                    break;
                }
                
                sleep(2); // Carrier-grade: Consider Redis pub/sub for true real-time
                
                // Double-check after sleep
                if (connection_aborted()) {
                    Log::info('SSE connection aborted after sleep', ['lastId' => $lastId]);
                    break;
                }
            }
            
            // Max iterations reached - client should reconnect
            if ($iteration >= $maxIterations) {
                Log::info('SSE max iterations reached - forcing reconnect', ['lastId' => $lastId]);
                echo "event: reconnect\ndata: {\"reason\": \"max_duration\"}\n\n";
                @ob_flush();
                @flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }
}
