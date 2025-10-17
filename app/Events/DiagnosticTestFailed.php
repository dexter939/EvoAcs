<?php

namespace App\Events;

use App\Models\DiagnosticTest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiagnosticTestFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public DiagnosticTest $test,
        public string $failureReason
    ) {}
}
