<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contractor extends Model
{
    protected $table = 'trendagent_contractors';

    protected $fillable = [
        'external_id',
        'name',
        'description',
        'logo_url',
        'website',
        'contact_phone',
        'contact_email',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    /**
     * Проекты
     */
    public function projects(): HasMany
    {
        return $this->hasMany(ContractorProject::class, 'contractor_id');
    }
}
