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
    ];

    protected $casts = [
        'images' => 'array',
        'raw_data' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
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
}
