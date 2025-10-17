<?php

namespace App\Http\Controllers;

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
}
