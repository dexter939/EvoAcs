<?php

namespace App\Jobs;

use App\Models\FirmwareDeployment;
use App\Models\ProvisioningTask;
use App\Services\TR069Service;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable as BusQueueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ProcessFirmwareDeployment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, BusQueueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(
        public FirmwareDeployment $deployment
    ) {}

    public function handle(): void
    {
        if ($this->deployment->status !== 'scheduled') {
            return;
        }

        try {
            $this->deployment->update([
                'status' => 'downloading',
                'started_at' => now()
            ]);

            $firmware = $this->deployment->firmwareVersion;
            $device = $this->deployment->cpeDevice;

            if (!$firmware || !$device) {
                throw new \Exception('Firmware or device not found');
            }

            $downloadUrl = url('/firmware/' . $firmware->file_path);
            
            $task = ProvisioningTask::create([
                'cpe_device_id' => $device->id,
                'task_type' => 'download',
                'status' => 'pending',
                'task_data' => [
                    'url' => $downloadUrl,
                    'file_type' => '1 Firmware Upgrade Image',
                    'file_size' => $firmware->file_size,
                    'firmware_id' => $firmware->id,
                    'deployment_id' => $this->deployment->id
                ]
            ]);

            ProcessProvisioningTask::dispatch($task);

            $this->deployment->update([
                'status' => 'installing',
            ]);

            Log::info('Firmware deployment initiated', [
                'deployment_id' => $this->deployment->id,
                'firmware_id' => $firmware->id,
                'device_id' => $device->id,
                'task_id' => $task->id
            ]);

        } catch (\Exception $e) {
            $this->deployment->increment('retry_count');
            
            if ($this->deployment->retry_count >= 3) {
                $this->deployment->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now()
                ]);
            } else {
                $this->deployment->update([
                    'status' => 'scheduled',
                    'error_message' => $e->getMessage()
                ]);
                
                $this->release(120);
            }

            Log::error('Firmware deployment failed', [
                'deployment_id' => $this->deployment->id,
                'error' => $e->getMessage(),
                'retry_count' => $this->deployment->retry_count
            ]);
        }
    }
}
