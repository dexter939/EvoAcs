<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * CacheService - Servizio centralizzato per gestione cache Redis
 * CacheService - Centralized service for Redis cache management
 * 
 * Ottimizza performance per gestione carrier-grade di 100,000+ dispositivi CPE
 * Optimizes performance for carrier-grade management of 100,000+ CPE devices
 */
class CacheService
{
    /**
     * TTL predefiniti per diversi tipi di dati (secondi)
     * Default TTL for different data types (seconds)
     */
    const TTL_DEVICE_DATA = 300;           // 5 minuti - Device basic info
    const TTL_DEVICE_PARAMETERS = 600;     // 10 minuti - TR-181 parameters
    const TTL_DEVICE_STATUS = 60;          // 1 minuto - Real-time status
    const TTL_PROFILES = 1800;             // 30 minuti - Configuration profiles
    const TTL_DATA_MODELS = 3600;          // 1 ora - TR-069 data models
    const TTL_STATISTICS = 300;            // 5 minuti - Dashboard statistics
    const TTL_TOPOLOGY = 180;              // 3 minuti - Network topology
    const TTL_SESSION = 120;               // 2 minuti - Active sessions
    
    /**
     * Recupera device data con caching
     * Get device data with caching
     * 
     * @param int $deviceId
     * @return mixed
     */
    public function getDeviceData($deviceId)
    {
        return Cache::remember("device:{$deviceId}:data", self::TTL_DEVICE_DATA, function () use ($deviceId) {
            return \App\Models\CpeDevice::with(['configurationProfile', 'service'])
                ->find($deviceId);
        });
    }
    
    /**
     * Recupera parametri device con caching
     * Get device parameters with caching
     * 
     * @param int $deviceId
     * @return mixed
     */
    public function getDeviceParameters($deviceId)
    {
        return Cache::remember("device:{$deviceId}:parameters", self::TTL_DEVICE_PARAMETERS, function () use ($deviceId) {
            return \App\Models\DeviceParameter::where('device_id', $deviceId)
                ->orderBy('parameter_name')
                ->get();
        });
    }
    
    /**
     * Recupera status device con TTL breve per real-time
     * Get device status with short TTL for real-time
     * 
     * @param int $deviceId
     * @return string
     */
    public function getDeviceStatus($deviceId)
    {
        return Cache::remember("device:{$deviceId}:status", self::TTL_DEVICE_STATUS, function () use ($deviceId) {
            $device = \App\Models\CpeDevice::select('status', 'last_inform', 'last_contact')
                ->find($deviceId);
            
            return $device ? [
                'status' => $device->status,
                'last_inform' => $device->last_inform,
                'last_contact' => $device->last_contact,
            ] : null;
        });
    }
    
    /**
     * Recupera lista dispositivi online con caching
     * Get online devices list with caching
     * 
     * @return mixed
     */
    public function getOnlineDevices()
    {
        return Cache::remember('devices:online', self::TTL_DEVICE_STATUS, function () {
            return \App\Models\CpeDevice::where('status', 'online')
                ->select('id', 'serial_number', 'manufacturer', 'model_name', 'status', 'last_inform')
                ->get();
        });
    }
    
    /**
     * Recupera configuration profile con caching
     * Get configuration profile with caching
     * 
     * @param int $profileId
     * @return mixed
     */
    public function getConfigurationProfile($profileId)
    {
        return Cache::remember("profile:{$profileId}", self::TTL_PROFILES, function () use ($profileId) {
            return \App\Models\ConfigurationProfile::find($profileId);
        });
    }
    
    /**
     * Recupera data model con caching
     * Get data model with caching
     * 
     * @param int $modelId
     * @return mixed
     */
    public function getDataModel($modelId)
    {
        return Cache::remember("datamodel:{$modelId}", self::TTL_DATA_MODELS, function () use ($modelId) {
            return \App\Models\Tr069DataModel::with('parameters')->find($modelId);
        });
    }
    
    /**
     * Recupera statistiche dashboard con caching
     * Get dashboard statistics with caching
     * 
     * @return array
     */
    public function getDashboardStatistics()
    {
        return Cache::remember('dashboard:statistics', self::TTL_STATISTICS, function () {
            return [
                'total_devices' => \App\Models\CpeDevice::count(),
                'online_devices' => \App\Models\CpeDevice::where('status', 'online')->count(),
                'offline_devices' => \App\Models\CpeDevice::where('status', 'offline')->count(),
                'provisioning_tasks' => \App\Models\ProvisioningTask::where('status', 'pending')->count(),
                'active_alarms' => \App\Models\Alarm::where('acknowledged', false)->count(),
                'recent_sessions' => \App\Models\Tr069Session::where('created_at', '>=', now()->subHours(24))->count(),
            ];
        });
    }
    
    /**
     * Recupera network topology con caching
     * Get network topology with caching
     * 
     * @param int $deviceId
     * @return mixed
     */
    public function getNetworkTopology($deviceId)
    {
        return Cache::remember("device:{$deviceId}:topology", self::TTL_TOPOLOGY, function () use ($deviceId) {
            return \App\Models\NetworkClient::where('device_id', $deviceId)
                ->where('active', true)
                ->orderBy('connection_type')
                ->get();
        });
    }
    
    /**
     * Invalida cache device (quando cambia configurazione/status)
     * Invalidate device cache (when configuration/status changes)
     * 
     * @param int $deviceId
     * @return void
     */
    public function invalidateDeviceCache($deviceId)
    {
        Cache::forget("device:{$deviceId}:data");
        Cache::forget("device:{$deviceId}:parameters");
        Cache::forget("device:{$deviceId}:status");
        Cache::forget("device:{$deviceId}:topology");
        Cache::forget('devices:online');
        Cache::forget('dashboard:statistics');
    }
    
    /**
     * Invalida cache profile
     * Invalidate profile cache
     * 
     * @param int $profileId
     * @return void
     */
    public function invalidateProfileCache($profileId)
    {
        Cache::forget("profile:{$profileId}");
    }
    
    /**
     * Invalida cache statistiche dashboard
     * Invalidate dashboard statistics cache
     * 
     * @return void
     */
    public function invalidateStatistics()
    {
        Cache::forget('dashboard:statistics');
    }
    
    /**
     * Recupera contatore da Redis per rate limiting
     * Get counter from Redis for rate limiting
     * 
     * @param string $key
     * @param int $ttl TTL in secondi
     * @return int
     */
    public function incrementCounter($key, $ttl = 60)
    {
        $redis = Redis::connection();
        $value = $redis->incr($key);
        
        if ($value == 1) {
            $redis->expire($key, $ttl);
        }
        
        return $value;
    }
    
    /**
     * Set session data in Redis con TTL
     * Set session data in Redis with TTL
     * 
     * @param string $sessionId
     * @param array $data
     * @return bool
     */
    public function setSessionData($sessionId, $data)
    {
        return Cache::put("session:{$sessionId}", $data, self::TTL_SESSION);
    }
    
    /**
     * Get session data from Redis
     * 
     * @param string $sessionId
     * @return mixed
     */
    public function getSessionData($sessionId)
    {
        return Cache::get("session:{$sessionId}");
    }
    
    /**
     * Bulk invalidate cache by pattern (usa Redis KEYS - attenzione in produzione)
     * Bulk invalidate cache by pattern (uses Redis KEYS - be careful in production)
     * 
     * @param string $pattern
     * @return void
     */
    public function invalidateByPattern($pattern)
    {
        $redis = Redis::connection();
        $keys = $redis->keys(config('cache.prefix') . ':' . $pattern);
        
        if (!empty($keys)) {
            $redis->del($keys);
        }
    }
    
    /**
     * Warm up cache per dispositivi critici
     * Warm up cache for critical devices
     * 
     * @param array $deviceIds
     * @return void
     */
    public function warmUpDeviceCache(array $deviceIds)
    {
        foreach ($deviceIds as $deviceId) {
            $this->getDeviceData($deviceId);
            $this->getDeviceParameters($deviceId);
            $this->getDeviceStatus($deviceId);
        }
    }
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getCacheStatistics()
    {
        $redis = Redis::connection();
        $info = $redis->info();
        
        return [
            'memory_used' => $info['used_memory_human'] ?? 'N/A',
            'memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
            'connected_clients' => $info['connected_clients'] ?? 0,
            'total_keys' => $redis->dbsize(),
            'hit_rate' => $this->calculateHitRate($info),
        ];
    }
    
    /**
     * Calculate cache hit rate
     * 
     * @param array $info
     * @return float
     */
    private function calculateHitRate($info)
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
}
