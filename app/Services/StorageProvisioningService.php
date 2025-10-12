<?php

namespace App\Services;

use App\Models\StorageService;
use App\Models\LogicalVolume;
use App\Models\FileServer;

class StorageProvisioningService
{
    public function mapStorageServiceToTR181(StorageService $storageService): array
    {
        $serviceInstance = $storageService->service_instance ?: 1;
        $basePath = "Device.Services.StorageService.{$serviceInstance}.";
        
        $parameters = [
            $basePath . 'Enable' => $storageService->enabled ? 'true' : 'false',
        ];

        return $parameters;
    }

    public function mapLogicalVolumeToTR181(LogicalVolume $volume): array
    {
        $storageService = $volume->storageService;
        $serviceInstance = $storageService->service_instance ?: 1;
        $volumeInstance = $volume->volume_instance ?: 1;
        
        $basePath = "Device.Services.StorageService.{$serviceInstance}.LogicalVolume.{$volumeInstance}.";
        
        $parameters = [
            $basePath . 'Enable' => $volume->enabled ? 'true' : 'false',
            $basePath . 'Name' => $volume->volume_name,
        ];

        if ($volume->mount_point) {
            $parameters[$basePath . 'X_MountPoint'] = $volume->mount_point;
        }

        if ($volume->auto_mount !== null) {
            $parameters[$basePath . 'X_AutoMount'] = $volume->auto_mount ? 'true' : 'false';
        }

        if ($volume->read_only !== null) {
            $parameters[$basePath . 'X_ReadOnly'] = $volume->read_only ? 'true' : 'false';
        }

        if ($volume->quota_enabled) {
            $parameters[$basePath . 'X_QuotaEnable'] = 'true';
            $parameters[$basePath . 'X_QuotaSize'] = $volume->quota_size;
        }

        if ($volume->encrypted) {
            $parameters[$basePath . 'X_Encrypted'] = 'true';
            $parameters[$basePath . 'X_EncryptionAlgorithm'] = $volume->encryption_algorithm;
        }

        return $parameters;
    }

    public function mapFileServerToTR181(FileServer $fileServer): array
    {
        $storageService = $fileServer->storageService;
        $serviceInstance = $storageService->service_instance ?: 1;
        $serverInstance = $fileServer->server_instance ?: 1;
        
        $basePath = "Device.Services.StorageService.{$serviceInstance}.FileServer.{$serverInstance}.";
        
        $parameters = [
            $basePath . 'Enable' => $fileServer->enabled ? 'true' : 'false',
            $basePath . 'Port' => $fileServer->port,
            $basePath . 'MaxConnections' => $fileServer->max_connections,
        ];

        if ($fileServer->bind_interface) {
            $parameters[$basePath . 'Interface'] = $fileServer->bind_interface;
        }

        if ($fileServer->document_root) {
            $parameters[$basePath . 'DocumentRoot'] = $fileServer->document_root;
        }

        if ($fileServer->anonymous_enabled !== null) {
            $parameters[$basePath . 'AnonymousEnable'] = $fileServer->anonymous_enabled ? 'true' : 'false';
        }

        if ($fileServer->passive_mode !== null) {
            $parameters[$basePath . 'FTP.PassiveMode'] = $fileServer->passive_mode ? 'true' : 'false';
            
            if ($fileServer->passive_port_min && $fileServer->passive_port_max) {
                $parameters[$basePath . 'FTP.PassivePortMin'] = $fileServer->passive_port_min;
                $parameters[$basePath . 'FTP.PassivePortMax'] = $fileServer->passive_port_max;
            }
        }

        if ($fileServer->ssl_enabled) {
            $parameters[$basePath . 'SSL.Enable'] = 'true';
        }

        if ($fileServer->auth_required !== null) {
            $parameters[$basePath . 'AuthRequired'] = $fileServer->auth_required ? 'true' : 'false';
        }

        if ($fileServer->allowed_users) {
            $parameters[$basePath . 'AllowedUsers'] = implode(',', $fileServer->allowed_users);
        }

        if ($fileServer->ip_whitelist) {
            $parameters[$basePath . 'IPWhitelist'] = implode(',', $fileServer->ip_whitelist);
        }

        return $parameters;
    }

    public function provisionCompleteStorageService(StorageService $storageService): array
    {
        $allParameters = [];

        $allParameters = array_merge(
            $allParameters,
            $this->mapStorageServiceToTR181($storageService)
        );

        $volumes = $storageService->logicalVolumes;
        
        foreach ($volumes as $volume) {
            $allParameters = array_merge(
                $allParameters,
                $this->mapLogicalVolumeToTR181($volume)
            );
        }

        $fileServers = $storageService->fileServers;
        
        foreach ($fileServers as $server) {
            $allParameters = array_merge(
                $allParameters,
                $this->mapFileServerToTR181($server)
            );
        }

        return $allParameters;
    }
}
