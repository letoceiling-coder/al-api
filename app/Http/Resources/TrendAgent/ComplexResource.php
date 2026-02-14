<?php

namespace App\Http\Resources\TrendAgent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Ресурс комплекса (ObjectCard, ObjectHeader).
 * UI_FIELD_MAP: _id, id, guid, name, address, image, images, min_price, min_prices, deadline, apart_count.
 */
class ComplexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = $this->resolveImageUrl();
        $images = $this->resolveImages();

        return [
            '_id' => $this->external_id,
            'id' => $this->external_id,
            'guid' => $this->guid,
            'name' => $this->name,
            'title' => $this->name,
            'block_name' => $this->name,
            'address' => $this->address,
            'description' => $this->description,
            'about' => $this->description,
            'min_price' => $this->min_price ? (int) $this->min_price : null,
            'price_from' => $this->min_price ? (int) $this->min_price : null,
            'min_prices' => $this->min_price ? [
                ['price' => $this->min_price, 'value' => $this->min_price, 'formatted_value' => number_format($this->min_price), 'label' => 'от', 'unit' => '₽'],
            ] : [],
            'deadline' => $this->deadline,
            'apart_count' => $this->when(isset($this->apartments_count), fn () => $this->apartments_count ?? $this->apartments()->count()),
            'image' => $imageUrl ? ['url' => $imageUrl, 'thumbnail' => $imageUrl, 'full' => $imageUrl] : null,
            'images' => $images,
            'renderer' => $images,
        ];
    }

    private function resolveImageUrl(): ?string
    {
        $images = $this->images;
        if (is_array($images) && !empty($images)) {
            $first = $images[0];
            if (is_string($first)) {
                return $first;
            }
            if (is_array($first) && isset($first['url'])) {
                return $first['url'];
            }
            if (is_array($first) && isset($first['path'], $first['file_name'])) {
                return 'https://selcdn.trendagent.ru/images/' . ($first['path'] ?? '') . '/' . ($first['file_name'] ?? '');
            }
        }
        $morphImages = $this->relationLoaded('images') ? $this->images : null;
        if ($morphImages && $morphImages->isNotEmpty()) {
            return $morphImages->first()->image_url ?? null;
        }
        return null;
    }

    private function resolveImages(int $limit = 2): array
    {
        $images = $this->images ?? [];
        $result = [];
        if (is_array($images)) {
            foreach (array_slice($images, 0, $limit) as $img) {
                if (is_string($img)) {
                    $result[] = ['url' => $img];
                } elseif (is_array($img)) {
                    $url = $img['url'] ?? null;
                    if (!$url && isset($img['path'], $img['file_name'])) {
                        $url = 'https://selcdn.trendagent.ru/images/' . ($img['path'] ?? '') . '/' . ($img['file_name'] ?? '');
                    }
                    $result[] = ['url' => $url, 'thumbnail' => $url, 'full' => $url];
                }
            }
        }
        if ($this->relationLoaded('images') && $this->images) {
            foreach ($this->images->take($limit) as $img) {
                $result[] = ['url' => $img->image_url ?? $img->url ?? ''];
            }
        }
        return $result;
    }
}
