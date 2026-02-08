<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complex extends Model
{
    protected $table = 'trendagent_complexes';

    protected $fillable = [
        'region_id',
        'external_id',
        'guid',
        'name',
        'address',
        'description',
        'latitude',
        'longitude',
        'developer_name',
        'class_type',
        'deadline',
        'status',
        'min_price',
        'images',
        'advantages',
        'nearby_places',
        'videos',
        'files',
        'raw_data',
    ];

    protected $casts = [
        'images' => 'array',
        'advantages' => 'array',
        'nearby_places' => 'array',
        'videos' => 'array',
        'files' => 'array',
        'raw_data' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'min_price' => 'integer',
    ];

    /**
     * Регион
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * Корпуса
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class, 'complex_id');
    }

    /**
     * Квартиры
     */
    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class, 'complex_id');
    }

    /**
     * Паркинги
     */
    public function parkings(): HasMany
    {
        return $this->hasMany(Parking::class, 'complex_id');
    }

    /**
     * Коммерция
     */
    public function commercial(): HasMany
    {
        return $this->hasMany(Commercial::class, 'complex_id');
    }

    /**
     * Ближайшие места
     */
    public function nearbyPlaces(): HasMany
    {
        return $this->hasMany(NearbyPlace::class, 'complex_id');
    }

    /**
     * Поэтажные планы
     */
    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class, 'complex_id');
    }
}
