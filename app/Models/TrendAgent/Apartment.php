<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apartment extends Model
{
    protected $table = 'trendagent_apartments';

    protected $fillable = [
        'complex_id',
        'building_id',
        'section_id',
        'floor_id',
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
    ];

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
}
