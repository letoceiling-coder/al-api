<?php

namespace App\Http\Controllers\TrendAgent\Db;

use App\Http\Controllers\Controller;
use App\Models\TrendAgent\Plot;
use App\Models\TrendAgent\PlotSettlement;
use App\Services\TrendAgent\CityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlotsDbController extends Controller
{
    private function regionFromCity(?string $city): ?string
    {
        if (!$city) {
            return null;
        }
        return CityService::getCityKeyById($city) ?? $city;
    }

    /**
     * POST /plots — список поселков (PlotSettlement).
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

        $query = PlotSettlement::query()->when($regionCode, fn ($q) => $q->region($regionCode));

        $total = $query->count();
        $items = $query->offset($offset)->limit($count)->get();

        $objects = $items->map(fn ($s) => [
            '_id' => $s->external_id,
            'id' => $s->external_id,
            'guid' => $s->guid,
            'name' => $s->name,
            'village_name' => $s->name,
            'address' => $s->address,
            'plots_count' => $s->plots()->count(),
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
     * POST /plots/{id} — детали поселка.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $settlement = PlotSettlement::with('plots')->where('external_id', $id)->orWhere('guid', $id)->orWhere('id', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'unified' => ['data' => [
                    '_id' => $settlement->external_id,
                    'id' => $settlement->external_id,
                    'guid' => $settlement->guid,
                    'name' => $settlement->name,
                    'address' => $settlement->address,
                ]],
                'plots' => ['data' => $settlement->plots->map(fn ($p) => [
                    'id' => $p->external_id ?? $p->id,
                    'number' => $p->number,
                    'area' => $p->area,
                    'price_base' => $p->price_base,
                ])->all()],
            ],
        ]);
    }

    /**
     * POST /plots/{id}/plot/{plotId} — детали участка.
     */
    public function plotDetail(Request $request, string $id, string $plotId): JsonResponse
    {
        $plot = Plot::with('settlement')->where('external_id', $plotId)->orWhere('id', $plotId)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $plot->external_id ?? $plot->id,
                'number' => $plot->number,
                'area' => $plot->area,
                'price_base' => $plot->price_base,
            ],
        ]);
    }
}
