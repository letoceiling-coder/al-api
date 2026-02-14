<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apartment extends Model
{
    protected $table = 'trendagent_apartments';

    protected $fillable = [
        'complex_id',
        'last_seen_at',
        'is_active',
        'building_id',
        'section_id',
        'floor_id',
        'region_id',
        'external_id',
        'number',
        'rooms',
        'area_total',
        'area_living',
        'area_kitchen',
        'floor',
        'price_base',
        'price_full',
        'price_per_sqm',
        'finishing_type_id',
        'status_id',
        'balcony_type_id',
        'view_type_id',
        'is_exclusive',
        'is_booked',
        'is_on_request',
        'plan_image_url',
        'images',
        'raw_data',
    ];

    protected $casts = [
        'images' => 'array',
        'raw_data' => 'array',
        'area_total' => 'decimal:2',
        'area_living' => 'decimal:2',
        'area_kitchen' => 'decimal:2',
        'rooms' => 'integer',
        'floor' => 'integer',
        'price_base' => 'integer',
        'price_full' => 'integer',
        'price_per_sqm' => 'integer',
        'is_exclusive' => 'boolean',
        'is_booked' => 'boolean',
        'is_on_request' => 'boolean',
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Регион (для фильтрации по city)
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

    /**
     * Корпус
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    /**
     * Секция
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * Этаж (модель)
     */
    public function floorModel(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'floor_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        });
    }

    /**
     * Фильтр по региону (region_id или region.code).
     * Контракт: city → Region.code (spb, msk).
     */
    public function scopeRegion(Builder $query, int|string $region): Builder
    {
        if (is_numeric($region)) {
            return $query->where('region_id', $region);
        }
        return $query->whereHas('region', fn (Builder $q) => $q->where('code', $region));
    }

    /**
     * Фильтр по цене (price_from, price_to).
     */
    public function scopePriceBetween(Builder $query, ?int $from, ?int $to): Builder
    {
        if ($from !== null) {
            $query->where('price_base', '>=', $from);
        }
        if ($to !== null) {
            $query->where('price_base', '<=', $to);
        }
        return $query;
    }

    /**
     * Фильтр по площади (area_from, area_to).
     */
    public function scopeAreaBetween(Builder $query, ?float $from, ?float $to): Builder
    {
        if ($from !== null) {
            $query->where('area_total', '>=', $from);
        }
        if ($to !== null) {
            $query->where('area_total', '<=', $to);
        }
        return $query;
    }

    /**
     * Фильтр по количеству комнат (room[]).
     */
    public function scopeRoomsIn(Builder $query, array $rooms): Builder
    {
        if (empty($rooms)) {
            return $query;
        }
        return $query->whereIn('rooms', $rooms);
    }

    /**
     * Сортировка (sort, sort_order).
     * sort: price|deadline|name → price_base|created_at (deadline нет в apartments), external_id (name)
     */
    public function scopeSort(Builder $query, string $sort = 'price', string $order = 'asc'): Builder
    {
        $column = match ($sort) {
            'area' => 'area_total',
            'deadline' => 'created_at',
            'name' => 'external_id',
            default => 'price_base',
        };
        return $query->orderBy($column, strtolower($order) === 'desc' ? 'desc' : 'asc');
    }
}
