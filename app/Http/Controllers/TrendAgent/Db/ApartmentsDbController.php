<?php

namespace App\Http\Controllers\TrendAgent\Db;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrendAgent\ApartmentDetailResource;
use App\Http\Resources\TrendAgent\ApartmentListResource;
use App\Http\Resources\TrendAgent\ComplexResource;
use App\Models\TrendAgent\Apartment;
use App\Models\TrendAgent\Complex;
use App\Services\TrendAgent\CityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApartmentsDbController extends Controller
{
    private function regionFromCity(?string $city): ?string
    {
        if (!$city) {
            return null;
        }
        return CityService::getCityKeyById($city) ?? $city;
    }

    /**
     * POST /apartments — список квартир (ObjectsTable).
     */
    public function index(Request $request): JsonResponse
    {
        return $this->listApartments($request);
    }

    public function listApartments(Request $request): JsonResponse
    {
        $count = max(1, min(100, (int) $request->input('count', 20)));
        $page = max(1, (int) $request->input('page', 1));
        $offset = (int) $request->input('offset', 0);
        if ($page > 1 && $offset === 0) {
            $offset = ($page - 1) * $count;
        }

        $regionCode = $this->regionFromCity($request->input('city'));
        $sort = $request->input('sort', 'price');
        $sortOrder = in_array(strtolower($request->input('sort_order', 'asc')), ['asc', 'desc']) ? strtolower($request->input('sort_order')) : 'asc';

        $includeInactive = (bool) $request->input('include_inactive', false);
        $query = Apartment::query()
            ->with('complex')
            ->when(!$includeInactive, fn ($q) => $q->active())
            ->when($regionCode, fn ($q) => $q->region($regionCode))
            ->priceBetween($request->input('price_from'), $request->input('price_to'))
            ->areaBetween($request->input('area_from'), $request->input('area_to'))
            ->roomsIn((array) $request->input('room', []))
            ->when($request->filled('text'), fn ($q) => $q->where(function ($q) use ($request) {
                $text = '%' . $request->input('text') . '%';
                $q->where('number', 'like', $text)
                    ->orWhereHas('complex', fn ($cq) => $cq->where('name', 'like', $text));
            }))
            ->sort($sort, $sortOrder);

        $total = $query->count();
        $items = $query->offset($offset)->limit($count)->get();

        return response()->json([
            'success' => true,
            'total_count' => $total,
            'data' => [
                'objects' => ApartmentListResource::collection($items),
                'apartments_count' => $total,
                'objects_count' => $items->count(),
            ],
            'pagination' => [
                'count' => $count,
                'offset' => $offset,
                'page' => $page,
                'current_page' => $page,
                'per_page' => $count,
                'has_more' => ($offset + $items->count()) < $total,
            ],
        ]);
    }

    /**
     * POST /objects/list object_type=blocks — список комплексов.
     */
    public function listComplexes(Request $request): JsonResponse
    {
        $count = max(1, min(100, (int) $request->input('count', 20)));
        $page = max(1, (int) $request->input('page', 1));
        $offset = (int) $request->input('offset', 0);
        if ($page > 1 && $offset === 0) {
            $offset = ($page - 1) * $count;
        }

        $regionCode = $this->regionFromCity($request->input('city'));
        $sort = $request->input('sort', 'price');
        $sortOrder = in_array(strtolower($request->input('sort_order', 'asc')), ['asc', 'desc']) ? strtolower($request->input('sort_order')) : 'asc';

        $includeInactive = (bool) $request->input('include_inactive', false);
        $query = Complex::query()
            ->withCount('apartments')
            ->when(!$includeInactive, fn ($q) => $q->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            }))
            ->when($regionCode, fn ($q) => $q->region($regionCode))
            ->when($request->filled('text'), fn ($q) => $q->where('name', 'like', '%' . $request->input('text') . '%'))
            ->sort($sort, $sortOrder);

        $total = $query->count();
        $items = $query->offset($offset)->limit($count)->get();

        return response()->json([
            'success' => true,
            'total_count' => $total,
            'data' => [
                'objects' => ComplexResource::collection($items)->resolve(),
                'blocks_count' => $total,
                'apartments_count' => $items->sum(fn ($c) => $c->apartments_count ?? 0),
            ],
            'pagination' => [
                'count' => $count,
                'offset' => $offset,
                'page' => $page,
                'current_page' => $page,
                'per_page' => $count,
                'has_more' => ($offset + $items->count()) < $total,
            ],
        ]);
    }

    /**
     * POST /apartments/{id} — детали комплекса (ObjectDetail).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $complex = Complex::query()
            ->where('external_id', $id)
            ->orWhere('guid', $id)
            ->orWhere('id', $id)
            ->with(['buildings', 'apartments', 'parkings', 'nearbyPlaces', 'floorPlans'])
            ->firstOrFail();

        $unified = (new ComplexResource($complex))->resolve();
        $unified['apart_count'] = $complex->apartments->count();

        $apartmentsData = $complex->apartments()
            ->with('complex')
            ->limit(500)
            ->get()
            ->map(fn ($a) => (new ApartmentListResource($a))->resolve())
            ->all();

        return response()->json([
            'success' => true,
            'block_id' => $complex->external_id,
            'block_guid' => $complex->guid,
            'data' => [
                'unified' => ['data' => $unified],
                'apartments' => ['data' => $apartmentsData, 'grouped_data' => []],
                'buildings' => ['data' => $complex->buildings->map(fn ($b) => [
                    'id' => $b->external_id ?? $b->id,
                    '_id' => $b->external_id,
                    'name' => $b->name,
                ])->all()],
                'parkings' => ['data' => []],
                'commerce' => ['data' => []],
                'plans' => ['data' => []],
                'progress' => (object) [],
                'finishings' => (object) [],
                'advantages' => ['data' => $complex->advantages ?? []],
                'nearby_places' => ['data' => $complex->nearbyPlaces->map(fn ($n) => [
                    'name' => $n->name ?? null,
                    'type' => $n->type ?? null,
                ])->all()],
                'block_id' => $complex->external_id,
                'block_guid' => $complex->guid,
            ],
        ]);
    }

    /**
     * POST /apartments/{id}/flat/{apartmentId} — детали квартиры.
     */
    public function flatDetail(Request $request, string $id, string $apartmentId): JsonResponse
    {
        $apartment = Apartment::query()
            ->with(['complex', 'building'])
            ->where(function ($q) use ($apartmentId) {
                $q->where('external_id', $apartmentId)
                    ->orWhere('id', $apartmentId);
            })
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => (new ApartmentDetailResource($apartment))->resolve(),
        ]);
    }
}
