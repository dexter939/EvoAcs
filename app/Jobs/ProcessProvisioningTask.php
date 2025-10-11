<?php

namespace App\Jobs;

use App\Models\ProvisioningTask;
use App\Models\CpeDevice;
use App\Services\TR069Service;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable as BusQueueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessProvisioningTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, BusQueueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public ProvisioningTask $task
    ) {}

    public function handle(): void
    {
        if ($this->task->status !== 'pending') {
            return;
        }

        $this->task->update([
            'status' => 'processing',
            'started_at' => now()
        ]);

        try {
            $device = $this->task->cpeDevice;
            
            if (!$device || !$device->connection_request_url) {
                throw new \Exception('Device not found or connection request URL missing');
            }

            $tr069Service = new TR069Service();
            $soapRequest = null;

            switch ($this->task->task_type) {
                case 'get_parameters':
                    $parameters = $this->task->task_data['parameters'] ?? [];
                    $soapRequest = $tr069Service->generateGetParameterValuesRequest($parameters);
                    break;

                case 'set_parameters':
                    $parameters = $this->task->task_data['parameters'] ?? [];
                    $soapRequest = $tr069Service->generateSetParameterValuesRequest($parameters);
                    break;

                case 'reboot':
                    $soapRequest = $tr069Service->generateRebootRequest();
                    break;

                case 'download':
                    $url = $this->task->task_data['url'] ?? '';
                    $fileType = $this->task->task_data['file_type'] ?? '1 Firmware Upgrade Image';
                    $fileSize = $this->task->task_data['file_size'] ?? 0;
                    $soapRequest = $tr069Service->generateDownloadRequest($url, $fileType, $fileSize);
                    break;
            }

            if ($soapRequest) {
                $response = Http::withHeaders([
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => ''
                ])
                ->withBasicAuth(
                    $device->connection_request_username ?? '',
                    $device->connection_request_password ?? ''
                )
                ->timeout(30)
                ->send('POST', $device->connection_request_url, [
                    'body' => $soapRequest
                ]);

                $this->task->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'result_data' => [
                        'response_code' => $response->status(),
                        'response_body' => $response->body()
                    ]
                ]);

                if ($this->task->task_type === 'download' && isset($this->task->task_data['deployment_id'])) {
                    $deployment = \App\Models\FirmwareDeployment::find($this->task->task_data['deployment_id']);
                    if ($deployment) {
                        $deployment->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                            'download_progress' => 100
                        ]);
                    }
                }

                Log::info('Provisioning task completed', [
                    'task_id' => $this->task->id,
                    'device_id' => $device->id,
                    'type' => $this->task->task_type
                ]);
            }

        } catch (\Exception $e) {
            $this->task->increment('retry_count');
            
            if ($this->task->retry_count >= $this->task->max_retries) {
                $this->task->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now()
                ]);
                
                if ($this->task->task_type === 'download' && isset($this->task->task_data['deployment_id'])) {
                    $deployment = \App\Models\FirmwareDeployment::find($this->task->task_data['deployment_id']);
                    if ($deployment) {
                        $deployment->update([
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                            'completed_at' => now()
                        ]);
                    }
                }
            } else {
                $this->task->update([
                    'status' => 'pending',
                    'error_message' => $e->getMessage()
                ]);
                
                $this->release(60);
            }

            Log::error('Provisioning task failed', [
                'task_id' => $this->task->id,
                'error' => $e->getMessage(),
                'retry_count' => $this->task->retry_count
            ]);
        }
    }
}
