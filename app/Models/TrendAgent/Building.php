<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    protected $table = 'trendagent_buildings';

    protected $fillable = [
        'complex_id',
        'external_id',
        'name',
        'number',
        'sections_count',
        'floors_count',
        'apartments_count',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'sections_count' => 'integer',
        'floors_count' => 'integer',
        'apartments_count' => 'integer',
    ];

    /**
     * Комплекс
     */
    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class, 'complex_id');
    }

    /**
     * Секции
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'building_id');
    }

    /**
     * Квартиры
     */
    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class, 'building_id');
    }
}
