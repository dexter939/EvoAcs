<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class UspXmppTransport
{
    protected XmppClientService $xmppClient;
    
    public function __construct(XmppClientService $xmppClient)
    {
        $this->xmppClient = $xmppClient;
    }

    public function sendUspMessage(string $deviceSerial, string $protobufMessage): bool
    {
        $cpeJid = $this->getCpeJid($deviceSerial);
        
        $base64Payload = base64_encode($protobufMessage);
        
        $uspStanza = $this->buildUspStanza($cpeJid, $base64Payload);
        
        if (!$this->xmppClient->sendRawStanza($uspStanza)) {
            Log::error('[USP-XMPP] Failed to send USP message', [
                'device_serial' => $deviceSerial,
                'cpe_jid' => $cpeJid,
            ]);
            
            return false;
        }

        Log::info('[USP-XMPP] USP message sent successfully', [
            'device_serial' => $deviceSerial,
            'cpe_jid' => $cpeJid,
            'payload_size' => strlen($protobufMessage),
        ]);

        return true;
    }

    public function receiveUspMessages(callable $callback, int $timeout = 30): void
    {
        $this->xmppClient->receiveStanzas(function ($response) use ($callback) {
            try {
                $uspPayload = $this->extractUspPayload($response);
                
                if ($uspPayload) {
                    $protobufMessage = base64_decode($uspPayload);
                    
                    $callback($protobufMessage, $response);
                    
                    Log::debug('[USP-XMPP] USP message received and processed', [
                        'payload_size' => strlen($protobufMessage),
                    ]);
                }
            } catch (Exception $e) {
                Log::error('[USP-XMPP] Failed to process received USP message', [
                    'error' => $e->getMessage(),
                ]);
            }
        }, $timeout);
    }

    protected function buildUspStanza(string $to, string $base64Payload): string
    {
        $from = $this->xmppClient->getJid();
        $messageId = uniqid('usp_', true);

        return sprintf(
            '<message to="%s" from="%s" id="%s" type="chat">' .
            '<body>%s</body>' .
            '<usp xmlns="urn:broadband-forum-org:usp:data-1-0">%s</usp>' .
            '</message>',
            htmlspecialchars($to),
            htmlspecialchars($from),
            htmlspecialchars($messageId),
            htmlspecialchars($base64Payload),
            htmlspecialchars($base64Payload)
        );
    }

    protected function extractUspPayload($response): ?string
    {
        if (!is_string($response)) {
            return null;
        }

        if (preg_match('/<usp[^>]*>(.*?)<\/usp>/s', $response, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/<body>(.*?)<\/body>/s', $response, $matches)) {
            $body = trim($matches[1]);
            
            if (strlen($body) > 0 && base64_decode($body, true) !== false) {
                return $body;
            }
        }

        return null;
    }

    protected function getCpeJid(string $deviceSerial): string
    {
        $format = config('xmpp.usp.cpe_jid_format', 'device-{serial}@acs.local');
        
        return str_replace('{serial}', $deviceSerial, $format);
    }

    public function isConnected(): bool
    {
        return $this->xmppClient->isConnected();
    }

    public function connect(): bool
    {
        return $this->xmppClient->connect();
    }
}
