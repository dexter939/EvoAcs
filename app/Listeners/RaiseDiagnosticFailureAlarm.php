<?php

namespace App\Listeners;

use App\Events\DiagnosticTestFailed;
use App\Services\AlarmService;

class RaiseDiagnosticFailureAlarm
{
    public function __construct(
        private AlarmService $alarmService
    ) {}

    public function handle(DiagnosticTestFailed $event): void
    {
        $severity = match($event->test->test_type) {
            'ping' => 'minor',
            'traceroute' => 'info',
            'download_diagnostics', 'upload_diagnostics' => 'warning',
            default => 'info',
        };

        $this->alarmService->raiseAlarm([
            'device_id' => $event->test->cpe_device_id,
            'alarm_type' => 'diagnostic_test_failed',
            'severity' => $severity,
            'category' => 'diagnostics',
            'title' => "Diagnostic Test Failed: {$event->test->test_type}",
            'description' => "Diagnostic test #{$event->test->id} ({$event->test->test_type}) failed: {$event->failureReason}",
            'metadata' => [
                'test_id' => $event->test->id,
                'test_type' => $event->test->test_type,
                'failure_reason' => $event->failureReason,
            ],
        ]);
    }
}
