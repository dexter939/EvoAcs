<?php

namespace App\Services;

use Pdahal\Xmpp\XmppClient;
use Pdahal\Xmpp\Options;
use Illuminate\Support\Facades\Log;
use Exception;

class XmppClientService
{
    protected ?XmppClient $client = null;
    protected string $host;
    protected int $port;
    protected string $username;
    protected string $domain;
    protected string $password;
    protected bool $connected = false;

    public function __construct()
    {
        $this->host = config('xmpp.host', '127.0.0.1');
        $this->port = config('xmpp.port', 6000);
        
        $jid = config('xmpp.jid', 'acs-server@acs.local');
        $parts = explode('@', $jid);
        $this->username = $parts[0];
        $this->domain = $parts[1] ?? 'acs.local';
        
        $this->password = config('xmpp.password');
        
        if (!$this->password) {
            throw new Exception('[XMPP] XMPP_PASSWORD must be set in .env for security');
        }
    }

    public function connect(): bool
    {
        try {
            if ($this->connected && $this->client) {
                return true;
            }

            $options = new Options();
            $options
                ->setHost($this->host)
                ->setPort($this->port)
                ->setUsername($this->username)
                ->setPassword($this->password);

            $this->client = new XmppClient($options);
            $this->client->connect();
            $this->connected = true;

            Log::info('[XMPP] Connected successfully', [
                'username' => $this->username,
                'host' => $this->host,
                'port' => $this->port,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('[XMPP] Connection failed', [
                'error' => $e->getMessage(),
                'username' => $this->username,
                'host' => $this->host,
                'port' => $this->port,
            ]);

            $this->connected = false;
            return false;
        }
    }

    public function disconnect(): void
    {
        try {
            if ($this->client && $this->connected) {
                $this->client->disconnect();
                Log::info('[XMPP] Disconnected successfully', [
                    'username' => $this->username,
                    'domain' => $this->domain,
                ]);
            }
        } catch (Exception $e) {
            Log::warning('[XMPP] Disconnect error', ['error' => $e->getMessage()]);
        } finally {
            $this->connected = false;
            $this->client = null;
        }
    }

    public function sendMessage(string $to, string $body, string $type = 'chat'): bool
    {
        if (!$this->connected) {
            if (!$this->connect()) {
                return false;
            }
        }

        try {
            $this->client->message->send($body, $to);

            Log::info('[XMPP] Message sent', [
                'to' => $to,
                'body_length' => strlen($body),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('[XMPP] Send message failed', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return false;
        }
    }

    public function sendRawStanza(string $stanza): bool
    {
        if (!$this->connected) {
            if (!$this->connect()) {
                return false;
            }
        }

        try {
            $this->client->send($stanza);

            Log::debug('[XMPP] Raw stanza sent', [
                'stanza_length' => strlen($stanza),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('[XMPP] Send raw stanza failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function receiveStanzas(callable $callback, int $timeout = 5): void
    {
        if (!$this->connected) {
            if (!$this->connect()) {
                Log::error('[XMPP] Cannot receive stanzas: not connected');
                return;
            }
        }

        try {
            $startTime = time();

            while ((time() - $startTime) < $timeout) {
                $response = $this->client->getResponse();

                if ($response) {
                    $callback($response);
                }

                usleep(100000);
            }
        } catch (Exception $e) {
            Log::error('[XMPP] Receive stanzas failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function getJid(): string
    {
        return $this->username . '@' . $this->domain;
    }
    
    public function getClient(): ?XmppClient
    {
        return $this->client;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
