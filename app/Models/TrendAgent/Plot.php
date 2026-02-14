<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plot extends Model
{
    protected $table = 'trendagent_plots';

    protected $fillable = [
        'region_id',
        'settlement_id',
        'external_id',
        'number',
        'area',
        'cadastral_number',
        'price_base',
        'utilities',
        'raw_data',
    ];

    protected $casts = [
        'utilities' => 'array',
        'raw_data' => 'array',
        'area' => 'decimal:2',
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

    /**
     * Поселок
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(PlotSettlement::class, 'settlement_id');
    }
}
