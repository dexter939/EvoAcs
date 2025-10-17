<?php

namespace App\Events;

use App\Models\Alarm;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlarmCreated
{
    use Dispatchable, SerializesModels;

    public Alarm $alarm;

    /**
     * Create a new event instance.
     */
    public function __construct(Alarm $alarm)
    {
        $this->alarm = $alarm;
    }
}
