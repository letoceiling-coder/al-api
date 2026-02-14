<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlotSettlement extends Model
{
    protected $table = 'trendagent_plot_settlements';

    protected $fillable = [
        'region_id',
        'external_id',
        'guid',
        'name',
        'address',
        'description',
        'latitude',
        'longitude',
        'images',
        'raw_data',
        'last_seen_at',
        'is_active',
    ];

    protected $casts = [
        'images' => 'array',
        'raw_data' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
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
     * Участки
     */
    public function plots(): HasMany
    {
        return $this->hasMany(Plot::class, 'settlement_id');
    }

    public function scopeRegion(Builder $query, int|string $region): Builder
    {
        if (is_numeric($region)) {
            return $query->where('region_id', $region);
        }
        return $query->whereHas('region', fn (Builder $q) => $q->where('code', $region));
    }
}
