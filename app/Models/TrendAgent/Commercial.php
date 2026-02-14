<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commercial extends Model
{
    protected $table = 'trendagent_commercial';

    protected $fillable = [
        'complex_id',
        'region_id',
        'external_id',
        'name',
        'commercial_type_id',
        'business_type_id',
        'area_total',
        'price_base',
        'rent_price',
        'floor',
        'images',
        'raw_data',
        'last_seen_at',
        'is_active',
    ];

    protected $casts = [
        'images' => 'array',
        'raw_data' => 'array',
        'area_total' => 'decimal:2',
        'price_base' => 'integer',
        'rent_price' => 'integer',
        'floor' => 'integer',
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

    /**
     * Комплекс
     */
    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class, 'complex_id');
    }

    public function scopeRegion(Builder $query, int|string $region): Builder
    {
        if (is_numeric($region)) {
            return $query->where('region_id', $region);
        }
        return $query->whereHas('region', fn (Builder $q) => $q->where('code', $region));
    }
}
