<?php

namespace App\Services;

use App\Models\CpeDevice;
use App\Models\StbService;
use App\Models\StreamingSession;

class StbProvisioningService
{
    public function provisionStbService(CpeDevice $cpeDevice, array $serviceData): StbService
    {
        return $cpeDevice->stbServices()->create([
            'service_type' => $serviceData['service_type'],
            'frontend_type' => $serviceData['frontend_type'],
            'streaming_protocol' => $serviceData['streaming_protocol'],
            'server_url' => $serviceData['server_url'] ?? null,
            'server_port' => $serviceData['server_port'] ?? null,
            'channel_list' => $serviceData['channel_list'] ?? [],
            'codec_settings' => $serviceData['codec_settings'] ?? [],
            'qos_parameters' => $serviceData['qos_parameters'] ?? [],
            'enabled' => $serviceData['enabled'] ?? true
        ]);
    }

    public function startStreamingSession(StbService $service, array $sessionData): StreamingSession
    {
        return $service->streamingSessions()->create([
            'session_id' => $sessionData['session_id'] ?? uniqid('stream_'),
            'channel_name' => $sessionData['channel_name'] ?? null,
            'content_url' => $sessionData['content_url'] ?? null,
            'status' => 'active',
            'started_at' => now()
        ]);
    }

    public function updateSessionQos(StreamingSession $session, array $qosData): StreamingSession
    {
        $session->update([
            'bitrate' => $qosData['bitrate'] ?? $session->bitrate,
            'packet_loss' => $qosData['packet_loss'] ?? $session->packet_loss,
            'jitter' => $qosData['jitter'] ?? $session->jitter,
            'qos_metrics' => array_merge($session->qos_metrics ?? [], $qosData['metrics'] ?? [])
        ]);
        return $session->fresh();
    }

    public function endSession(StreamingSession $session): StreamingSession
    {
        $session->update(['status' => 'stopped', 'ended_at' => now()]);
        return $session->fresh();
    }
}
