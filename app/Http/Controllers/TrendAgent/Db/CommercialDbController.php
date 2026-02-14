<?php

namespace App\Http\Controllers\TrendAgent\Db;

use App\Http\Controllers\Controller;
use App\Models\TrendAgent\Commercial;
use App\Services\TrendAgent\CityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommercialDbController extends Controller
{
    private function regionFromCity(?string $city): ?string
    {
        if (!$city) {
            return null;
        }
        return CityService::getCityKeyById($city) ?? $city;
    }

    /**
     * POST /commercial — список коммерции.
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

        $query = Commercial::query()
            ->with('complex')
            ->when($regionCode, fn ($q) => $q->region($regionCode));

        $total = $query->count();
        $items = $query->offset($offset)->limit($count)->get();

        $objects = $items->map(fn ($c) => [
            '_id' => $c->external_id,
            'id' => $c->external_id,
            'name' => $c->name,
            'min_price' => $c->price_base,
            'area_total' => $c->area_total,
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
     * POST /commercial/{id} — детали коммерции.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $commercial = Commercial::with('complex')->where('external_id', $id)->orWhere('id', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                '_id' => $commercial->external_id,
                'id' => $commercial->external_id,
                'name' => $commercial->name,
                'area_total' => $commercial->area_total,
                'price_base' => $commercial->price_base,
                'rent_price' => $commercial->rent_price,
            ],
        ]);
    }
}
