<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FloorPlan extends Model
{
    protected $table = 'trendagent_floor_plans';

    protected $fillable = [
        'complex_id',
        'building_id',
        'section_id',
        'floor_id',
        'floor_number',
        'image_url',
        'interactive_data',
        'raw_data',
    ];

    protected $casts = [
        'interactive_data' => 'array',
        'raw_data' => 'array',
        'floor_number' => 'integer',
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
     * Этаж
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'floor_id');
    }
}
