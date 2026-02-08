<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NearbyPlace extends Model
{
    protected $table = 'trendagent_nearby_places';

    protected $fillable = [
        'complex_id',
        'name',
        'type',
        'distance',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'distance' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Комплекс
     */
    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class, 'complex_id');
    }
}
