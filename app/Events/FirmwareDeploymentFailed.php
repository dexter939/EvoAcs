<?php

namespace App\Events;

use App\Models\FirmwareDeployment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FirmwareDeploymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public FirmwareDeployment $deployment,
        public string $errorMessage
    ) {}
}
