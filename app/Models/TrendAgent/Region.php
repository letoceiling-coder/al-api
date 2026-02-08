<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $table = 'trendagent_regions';

    protected $fillable = [
        'code',
        'name',
        'external_id',
    ];

    /**
     * Комплексы в регионе
     */
    public function complexes(): HasMany
    {
        return $this->hasMany(Complex::class, 'region_id');
    }

    /**
     * Дома в регионе
     */
    public function houses(): HasMany
    {
        return $this->hasMany(House::class, 'region_id');
    }

    /**
     * Поселки в регионе
     */
    public function plotSettlements(): HasMany
    {
        return $this->hasMany(PlotSettlement::class, 'region_id');
    }
}
