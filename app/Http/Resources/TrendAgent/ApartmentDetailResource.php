<?php

namespace App\Http\Resources\TrendAgent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс деталей квартиры (FlatDetail, Blade modal).
 * UI_FIELD_MAP: number, floor, total_floors, section_name, building_name, area, area_total, base_price, plan, images.
 */
class ApartmentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $complex = $this->whenLoaded('complex');
        $building = $this->whenLoaded('building');
        $planUrl = $this->plan_image_url;

        return [
            'id' => (string) $this->id,
            '_id' => $this->external_id,
            'external_id' => $this->external_id,
            'number' => $this->number,
            'apartment_number' => $this->number,
            'floor' => $this->floor,
            'total_floors' => $complex?->raw_data['floors'] ?? null,
            'section_name' => null,
            'section' => null,
            'building_name' => $building?->name ?? $complex?->name ?? null,
            'building' => $building?->name ?? $complex?->name ?? null,
            'corpus' => null,
            'privArea' => $this->area_total ? (float) $this->area_total : null,
            'area' => $this->area_total ? (float) $this->area_total : null,
            'area_total' => $this->area_total ? (float) $this->area_total : null,
            'calculated_area' => $this->area_total ? (float) $this->area_total : null,
            'kitchenArea' => $this->area_kitchen ? (float) $this->area_kitchen : null,
            'kitchen_area' => $this->area_kitchen ? (float) $this->area_kitchen : null,
            'livingArea' => $this->area_living ? (float) $this->area_living : null,
            'living_area' => $this->area_living ? (float) $this->area_living : null,
            'area_living' => $this->area_living ? (float) $this->area_living : null,
            'area_kitchen' => $this->area_kitchen ? (float) $this->area_kitchen : null,
            'base_price' => $this->price_base,
            'price' => $this->price_base,
            'full_price' => $this->price_full,
            'price_base' => $this->price_base,
            'price_full' => $this->price_full,
            'price_per_sqm' => $this->price_per_sqm,
            'status' => $this->whenLoaded('status', fn () => ['name' => $this->status->name ?? null]),
            'booking_status' => $this->is_booked ? 'booked' : null,
            'is_booked' => $this->is_booked ?? false,
            'is_on_request' => $this->is_on_request ?? false,
            'plan_image_url' => $planUrl,
            'plan' => $planUrl ? ['url' => $planUrl] : null,
            'plan_image' => $planUrl ? ['url' => $planUrl] : null,
            'images' => $this->formatImages($this->images ?? []),
            'gallery_images' => $this->formatImages($this->images ?? []),
        ];
    }

    private function formatImages(array $images): array
    {
        if (empty($images)) {
            return [];
        }
        $result = [];
        foreach ($images as $img) {
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
