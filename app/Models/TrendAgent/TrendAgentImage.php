<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

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
        'mime',
        'size',
        'hash',
        'download_status',
        'downloaded_at',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'size' => 'integer',
        'downloaded_at' => 'datetime',
    ];

    protected $appends = ['image_url'];

    /**
     * Полиморфная связь с владельцем изображения.
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'object_type', 'object_id');
    }

    /**
     * URL изображения: при local_path — Storage::url, иначе — оригинальный url.
     * Контракт db_api_contract.md, раздел 5.
     */
    public function getImageUrlAttribute(): string
    {
        if (!empty($this->local_path)) {
            return Storage::url($this->local_path);
        }
        return $this->url ?? '';
    }
}
