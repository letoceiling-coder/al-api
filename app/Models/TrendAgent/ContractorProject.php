<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorProject extends Model
{
    protected $table = 'trendagent_contractor_projects';

    protected $fillable = [
        'contractor_id',
        'external_id',
        'guid',
        'name',
        'description',
        'min_price',
        'area_total',
        'area_living',
        'construction_time',
        'technology',
        'images',
        'raw_data',
    ];

    protected $casts = [
        'images' => 'array',
        'raw_data' => 'array',
        'area_total' => 'decimal:2',
        'area_living' => 'decimal:2',
        'min_price' => 'integer',
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Подрядчик
     */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }
}
