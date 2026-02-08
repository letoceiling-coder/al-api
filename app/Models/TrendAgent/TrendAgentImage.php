<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;

class TrendAgentImage extends Model
{
    protected $table = 'trendagent_images';

    protected $fillable = [
        'object_type',
        'object_id',
        'url',
        'type',
        'order_index',
        'local_path',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];
}
