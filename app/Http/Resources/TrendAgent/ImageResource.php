<?php

namespace App\Http\Resources\TrendAgent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $url = $this->image_url ?? $this->url ?? '';

        return [
            'url' => $url,
            'thumbnail' => $url,
            'full' => $url,
            'path' => $this->path ?? null,
            'file_name' => $this->file_name ?? null,
        ];
    }
}
