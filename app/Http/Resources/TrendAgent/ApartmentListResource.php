<?php

namespace App\Http\Resources\TrendAgent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс квартиры для списка (ObjectCard, ApartmentsTable).
 * UI_FIELD_MAP: _id, id, number, rooms, area_total, floor, base_price, plan_image, building_name, section_name, deadline.
 */
class ApartmentListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $complex = $this->whenLoaded('complex');
        $planUrl = $this->plan_image_url;

        return [
            '_id' => $this->external_id,
            'id' => (string) $this->id,
            'external_id' => $this->external_id,
            'number' => $this->number,
            'rooms' => $this->rooms,
            'area_total' => $this->area_total ? (float) $this->area_total : null,
            'area' => $this->area_total ? (float) $this->area_total : null,
            'privArea' => $this->area_total ? (float) $this->area_total : null,
            'floor' => $this->floor,
            'base_price' => $this->price_base,
            'price' => $this->price_base,
            'price_base' => $this->price_base,
            'plan_image_url' => $planUrl,
            'plan_image' => $planUrl ? ['url' => $planUrl] : null,
            'plan' => $planUrl ? ['url' => $planUrl] : null,
            'building_name' => $complex?->name ?? null,
            'section_name' => null,
            'queue' => null,
            'deadline' => $complex?->deadline ?? null,
            'block_id' => $complex?->external_id ?? null,
            'block_guid' => $complex?->guid ?? null,
            'complex_id' => $this->complex_id,
            'is_booked' => $this->is_booked ?? false,
            'is_exclusive' => $this->is_exclusive ?? false,
            'images' => $this->formatImages($this->images ?? []),
        ];
    }

    private function formatImages(array $images, int $limit = 2): array
    {
        if (empty($images)) {
            return [];
        }
        $result = [];
        foreach (array_slice($images, 0, $limit) as $img) {
            if (is_string($img)) {
                $result[] = ['url' => $img];
            } elseif (is_array($img)) {
                $result[] = [
                    'url' => $img['url'] ?? $img['image_url'] ?? null,
                    'thumbnail' => $img['thumbnail'] ?? null,
                    'path' => $img['path'] ?? null,
                    'file_name' => $img['file_name'] ?? null,
                ];
            }
        }
        return $result;
    }
}
