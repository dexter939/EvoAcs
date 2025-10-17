<?php

namespace App\Events;

use App\Models\CpeDevice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceWentOffline
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CpeDevice $device
    ) {}
}
