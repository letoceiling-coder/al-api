<?php

namespace App\Http\Controllers\TrendAgent\Db;

use App\Http\Controllers\Controller;
use App\Models\TrendAgent\House;
use App\Services\TrendAgent\CityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HousesDbController extends Controller
{
    private function regionFromCity(?string $city): ?string
    {
        if (!$city) {
            return null;
        }
        return CityService::getCityKeyById($city) ?? $city;
    }

    /**
     * POST /houses — список домов.
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

        $query = House::query()->when($regionCode, fn ($q) => $q->region($regionCode));

        $total = $query->count();
        $items = $query->offset($offset)->limit($count)->get();

        $objects = $items->map(fn ($h) => [
            '_id' => $h->external_id,
            'id' => $h->external_id,
            'guid' => $h->guid,
            'name' => $h->name,
            'address' => $h->address,
            'min_price' => $h->price_base,
            'price' => $h->price_base,
        ])->all();

        return response()->json([
            'success' => true,
            'total_count' => $total,
            'data' => ['objects' => $objects],
            'pagination' => [
                'count' => $count,
                'offset' => $offset,
                'page' => $page,
                'has_more' => ($offset + $items->count()) < $total,
            ],
        ]);
    }

    /**
     * POST /houses/{id} — детали дома.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $house = House::where('external_id', $id)->orWhere('guid', $id)->orWhere('id', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'unified' => ['data' => [
                    '_id' => $house->external_id,
                    'id' => $house->external_id,
                    'guid' => $house->guid,
                    'name' => $house->name,
                    'address' => $house->address,
                    'min_price' => $house->price_base,
                    'apartments' => ['data' => []],
                ]],
            ],
        ]);
    }
}
