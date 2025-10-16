<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileServer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'storage_service_id',
        'server_instance',
        'server_name',
        'server_type',
        'enabled',
        'port',
        'bind_interface',
        'max_connections',
        'timeout',
        'welcome_message',
        'anonymous_enabled',
        'anonymous_directory',
        'passive_mode',
        'passive_port_min',
        'passive_port_max',
        'document_root',
        'share_path',
        'directory_listing',
        'ssl_enabled',
        'ssl_certificate',
        'ssl_private_key',
        'workgroup',
        'server_string',
        'guest_ok',
        'auth_required',
        'auth_method',
        'allowed_users',
        'allowed_groups',
        'ip_whitelist',
        'ip_blacklist',
        'logging_enabled',
        'log_level',
        'log_file_path',
        'total_connections',
        'total_uploads',
        'total_downloads',
        'bytes_uploaded',
        'bytes_downloaded',
        'last_connection',
        'status',
        'current_connections',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'anonymous_enabled' => 'boolean',
        'passive_mode' => 'boolean',
        'directory_listing' => 'boolean',
        'ssl_enabled' => 'boolean',
        'guest_ok' => 'boolean',
        'auth_required' => 'boolean',
        'logging_enabled' => 'boolean',
        'allowed_users' => 'array',
        'allowed_groups' => 'array',
        'ip_whitelist' => 'array',
        'ip_blacklist' => 'array',
        'last_connection' => 'datetime',
    ];

    protected $hidden = [
        'ssl_private_key',
    ];

    public function storageService(): BelongsTo
    {
        return $this->belongsTo(StorageService::class);
    }

    public function getDefaultPortAttribute(): int
    {
        return match($this->server_type) {
            'FTP' => 21,
            'SFTP' => 22,
            'HTTP' => 80,
            'HTTPS' => 443,
            'SAMBA' => 445,
            'NFS' => 2049,
            default => 21
        };
    }
}
