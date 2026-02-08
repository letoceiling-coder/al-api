<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class House extends Model
{
    protected $table = 'trendagent_houses';

    protected $fillable = [
        'region_id',
        'external_id',
        'guid',
        'name',
        'address',
        'land_area',
        'house_area',
        'floors_count',
        'rooms_count',
        'price_base',
        'images',
        'raw_data',
    ];

    protected $casts = [
        'images' => 'array',
        'raw_data' => 'array',
        'land_area' => 'decimal:2',
        'house_area' => 'decimal:2',
        'floors_count' => 'integer',
        'rooms_count' => 'integer',
        'price_base' => 'integer',
    ];

    /**
     * Регион
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}
