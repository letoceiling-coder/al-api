<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
        'last_seen_at',
        'is_active',
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

    /**
     * Изображения (полиморфная связь через object_type/object_id).
     */
    public function images(): MorphMany
    {
        return $this->morphMany(TrendAgentImage::class, 'imageable');
    }

    /**
     * Фильтр по региону (region_id или code).
     */
    public function scopeRegion(Builder $query, int|string $region): Builder
    {
        if (is_numeric($region)) {
            return $query->where('region_id', $region);
        }
        return $query->whereHas('region', fn (Builder $q) => $q->where('code', $region));
    }

    /**
     * Сортировка (sort: price|deadline|name).
     */
    public function scopeSort(Builder $query, string $sort = 'price', string $order = 'asc'): Builder
    {
        $column = match ($sort) {
            'deadline' => 'deadline',
            'name' => 'name',
            default => 'min_price',
        };
        return $query->orderBy($column, strtolower($order) === 'desc' ? 'desc' : 'asc');
    }
}
