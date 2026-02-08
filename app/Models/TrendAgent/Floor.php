<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    protected $table = 'trendagent_floors';

    protected $fillable = [
        'section_id',
        'external_id',
        'number',
        'apartments_count',
        'plan_image_url',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'number' => 'integer',
        'apartments_count' => 'integer',
    ];

    /**
     * Секция
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * Квартиры
     */
    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class, 'floor_id');
    }
}
