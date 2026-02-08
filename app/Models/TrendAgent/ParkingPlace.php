<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingPlace extends Model
{
    protected $table = 'trendagent_parking_places';

    protected $fillable = [
        'parking_id',
        'external_id',
        'number',
        'level',
        'status_id',
        'price',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'level' => 'integer',
        'price' => 'integer',
    ];

    /**
     * Паркинг
     */
    public function parking(): BelongsTo
    {
        return $this->belongsTo(Parking::class, 'parking_id');
    }
}
