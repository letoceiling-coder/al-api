<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parking extends Model
{
    protected $table = 'trendagent_parkings';

    protected $fillable = [
        'complex_id',
        'external_id',
        'name',
        'parking_type_id',
        'total_places',
        'available_places',
        'price_base',
        'price_per_month',
        'images',
        'raw_data',
    ];

    protected $casts = [
        'images' => 'array',
        'raw_data' => 'array',
        'total_places' => 'integer',
        'available_places' => 'integer',
        'price_base' => 'integer',
        'price_per_month' => 'integer',
    ];

    /**
     * Комплекс
     */
    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class, 'complex_id');
    }

    /**
     * Места парковки
     */
    public function places(): HasMany
    {
        return $this->hasMany(ParkingPlace::class, 'parking_id');
    }
}
