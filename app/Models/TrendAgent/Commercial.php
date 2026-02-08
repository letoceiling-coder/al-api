<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commercial extends Model
{
    protected $table = 'trendagent_commercial';

    protected $fillable = [
        'complex_id',
        'external_id',
        'name',
        'commercial_type_id',
        'business_type_id',
        'area_total',
        'price_base',
        'rent_price',
        'floor',
        'images',
        'raw_data',
    ];

    protected $casts = [
        'images' => 'array',
        'raw_data' => 'array',
        'area_total' => 'decimal:2',
        'price_base' => 'integer',
        'rent_price' => 'integer',
        'floor' => 'integer',
    ];

    /**
     * Комплекс
     */
    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class, 'complex_id');
    }
}
