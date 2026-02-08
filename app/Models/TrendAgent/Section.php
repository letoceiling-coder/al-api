<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $table = 'trendagent_sections';

    protected $fillable = [
        'building_id',
        'external_id',
        'name',
        'number',
        'floors_count',
        'apartments_count',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'floors_count' => 'integer',
        'apartments_count' => 'integer',
    ];

    /**
     * Корпус
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    /**
     * Этажи
     */
    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class, 'section_id');
    }

    /**
     * Квартиры
     */
    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class, 'section_id');
    }
}
