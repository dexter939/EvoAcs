<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TR069Parameter - Model per parametri TR-069/369
 * 
 * @property int $id
 * @property int $data_model_id
 * @property string $parameter_path
 * @property string $parameter_name
 * @property string $parameter_type
 * @property string $access_type
 * @property bool $is_object
 * @property string|null $description
 * @property string|null $default_value
 * @property string|null $min_version
 * @property array|null $validation_rules
 * @property array|null $metadata
 */
class TR069Parameter extends Model
{
    use HasFactory;

    protected $table = 'tr069_parameters';

    protected $fillable = [
        'data_model_id',
        'parameter_path',
        'parameter_name',
        'parameter_type',
        'access_type',
        'is_object',
        'description',
        'default_value',
        'min_version',
        'validation_rules',
        'metadata',
    ];

    protected $casts = [
        'is_object' => 'boolean',
        'validation_rules' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Relazione con Data Model TR-069
     * 
     * @return BelongsTo
     */
    public function dataModel(): BelongsTo
    {
        return $this->belongsTo(TR069DataModel::class, 'data_model_id');
    }
}
