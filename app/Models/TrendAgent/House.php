<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Builder;
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
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Регион
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function scopeRegion(Builder $query, int|string $region): Builder
    {
        if (is_numeric($region)) {
            return $query->where('region_id', $region);
        }
        return $query->whereHas('region', fn (Builder $q) => $q->where('code', $region));
    }
}
