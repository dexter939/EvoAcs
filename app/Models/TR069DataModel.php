<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TR069DataModel - Model per Data Model TR-069/369
 * 
 * @property int $id
 * @property string $vendor
 * @property string $model_name
 * @property string|null $firmware_version
 * @property string $protocol_version
 * @property string|null $spec_name
 * @property string|null $description
 * @property array|null $metadata
 * @property bool $is_active
 */
class TR069DataModel extends Model
{
    use HasFactory;

    protected $table = 'tr069_data_models';

    protected $fillable = [
        'vendor',
        'model_name',
        'firmware_version',
        'protocol_version',
        'spec_name',
        'description',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relazione con dispositivi CPE
     * 
     * @return HasMany
     */
    public function devices(): HasMany
    {
        return $this->hasMany(CpeDevice::class, 'data_model_id');
    }

    /**
     * Relazione con parametri TR-069
     * 
     * @return HasMany
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(TR069Parameter::class, 'data_model_id');
    }
}
