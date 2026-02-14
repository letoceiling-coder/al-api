<?php

namespace App\Http\Controllers\TrendAgent\Db;

use App\Http\Controllers\Controller;
use App\Models\TrendAgent\Parking;
use App\Services\TrendAgent\CityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParkingsDbController extends Controller
{
    private function regionFromCity(?string $city): ?string
    {
        if (!$city) {
            return null;
        }
        return CityService::getCityKeyById($city) ?? $city;
    }

    /**
     * POST /parkings — список паркингов.
     */
    public function index(Request $request): JsonResponse
    {
        $count = max(1, min(100, (int) $request->input('count', 20)));
        $page = max(1, (int) $request->input('page', 1));
        $offset = (int) $request->input('offset', 0);
        if ($page > 1 && $offset === 0) {
            $offset = ($page - 1) * $count;
        }

        $regionCode = $this->regionFromCity($request->input('city'));

        $query = Parking::query()
            ->with('complex')
            ->when($regionCode, fn ($q) => $q->region($regionCode));

        $total = $query->count();
        $items = $query->offset($offset)->limit($count)->get();

        $objects = $items->map(fn ($p) => [
            '_id' => $p->external_id,
            'id' => $p->external_id,
            'name' => $p->name,
            'places_count' => $p->total_places ?? 0,
            'min_price' => $p->price_base,
            'price' => $p->price_base,
        ])->all();

        return response()->json([
            'success' => true,
            'total_count' => $total,
            'data' => [
                'objects' => $objects,
            ],
            'pagination' => [
                'count' => $count,
                'offset' => $offset,
                'page' => $page,
                'has_more' => ($offset + $items->count()) < $total,
            ],
        ]);
    }

    /**
     * POST /parkings/{id} — детали паркинга.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $parking = Parking::with('complex', 'places')->where('external_id', $id)->orWhere('id', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                '_id' => $parking->external_id,
                'id' => $parking->external_id,
                'name' => $parking->name,
                'total_places' => $parking->total_places,
                'available_places' => $parking->available_places,
                'price_base' => $parking->price_base,
            ],
        ]);
    }

    /**
     * POST /parkings/{id}/places.
     */
    public function places(Request $request, string $id): JsonResponse
    {
        $parking = Parking::with('places')->where('external_id', $id)->orWhere('id', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'objects' => $parking->places->map(fn ($p) => [
                    'id' => $p->external_id ?? $p->id,
                    'number' => $p->number ?? null,
                ])->all(),
            ],
        ]);
    }
}
