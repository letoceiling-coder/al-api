<?php

namespace App\Http\Controllers\TrendAgent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TrendAgent\Apartment;
use App\Models\TrendAgent\Complex;
use App\Models\TrendAgent\Parking;
use App\Models\TrendAgent\House;
use App\Models\TrendAgent\Plot;
use App\Models\TrendAgent\Commercial;
use App\Models\TrendAgent\Contractor;
use App\Models\TrendAgent\Region;
use App\Services\TrendAgent\CityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TrendAgentDbController extends Controller
{
    /**
     * Получить детальную информацию о квартире
     */
    public function getApartmentDetails($id)
    {
        $apartment = Apartment::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apartment->id,
                'external_id' => $apartment->external_id,
                'number' => $apartment->number,
                'rooms' => $apartment->rooms,
                'area_total' => $apartment->area_total,
                'area_living' => $apartment->area_living,
                'area_kitchen' => $apartment->area_kitchen,
                'floor' => $apartment->floor,
                'price_base' => $apartment->price_base,
                'price_full' => $apartment->price_full,
                'price_per_sqm' => $apartment->price_per_sqm,
                'is_exclusive' => $apartment->is_exclusive,
                'is_booked' => $apartment->is_booked,
                'is_on_request' => $apartment->is_on_request,
                'plan_image_url' => $apartment->plan_image_url,
                'images' => $apartment->images ?? [],
                'raw_data' => $apartment->raw_data ?? [],
                'complex' => $apartment->complex ? [
                    'id' => $apartment->complex->id,
                    'name' => $apartment->complex->name,
                    'address' => $apartment->complex->address,
                ] : null,
            ],
        ]);
    }

    /**
     * Отображение интерфейса базы данных TrendAgent
     */
    public function index(Request $request)
    {
        // Получаем параметры фильтрации
        $region = $request->get('region', 'all');
        $type = $request->get('type', 'apartments'); // apartments, complexes, parkings, houses, plots, commercial, contractors
        $page = (int)$request->get('page', 1);
        $perPage = 50;
        
        // Статистика по всем типам объектов (кэшируем на 5 минут)
        $statistics = Cache::remember('trendagent_db_statistics', 300, function () {
            return [
                'apartments' => Apartment::count(),
                'complexes' => Complex::count(),
                'parkings' => Parking::count(),
                'houses' => House::count(),
                'plots' => Plot::count(),
                'commercial' => Commercial::count(),
                'contractors' => Contractor::count(),
            ];
        });
        
        // Статистика по регионам (кэшируем на 5 минут)
        $regions = Region::all();
        $regionStats = Cache::remember('trendagent_db_region_stats', 300, function () use ($regions) {
            $stats = [];
            // Проверяем наличие виртуальной колонки
            $hasVirtualColumn = !empty(DB::select("SHOW COLUMNS FROM trendagent_apartments LIKE 'city_id_extracted'"));
            
            foreach ($regions as $reg) {
                $cityInfo = CityService::getCityByKey($reg->code);
                if ($cityInfo && isset($cityInfo['id'])) {
                    $query = Apartment::query();
                    if ($hasVirtualColumn) {
                        $count = $query->where('city_id_extracted', $cityInfo['id'])->count();
                    } else {
                        $count = $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) = ?", [$cityInfo['id']])->count();
                    }
                    $stats[$reg->code] = [
                        'name' => $reg->name,
                        'count' => $count,
                    ];
                }
            }
            return $stats;
        });
        
        // Получаем данные в зависимости от типа
        $data = [];
        $total = 0;
        
        switch ($type) {
            case 'apartments':
                $query = Apartment::select([
                    'id', 'external_id', 'number', 'rooms', 'area_total', 'area_living', 
                    'area_kitchen', 'floor', 'price_base', 'price_full', 'price_per_sqm',
                    'is_exclusive', 'is_booked', 'is_on_request', 'plan_image_url', 
                    'images', 'raw_data', 'complex_id', 'created_at'
                ]);

                // Проверяем наличие виртуальной колонки для ускоренного фильтра по городу
                $hasVirtualColumn = !empty(DB::select("SHOW COLUMNS FROM trendagent_apartments LIKE 'city_id_extracted'"));

                if ($region !== 'all') {
                    $cityInfo = CityService::getCityByKey($region);
                    if ($cityInfo && isset($cityInfo['id'])) {
                        if ($hasVirtualColumn) {
                            $query->where('city_id_extracted', $cityInfo['id']);
                        } else {
                            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) = ?", [$cityInfo['id']]);
                        }
                    }
                }
                $total = $query->count();
                $data = $query->orderBy('created_at', 'desc')
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->get();
                break;
                
            case 'complexes':
                $query = Complex::query();
                if ($region !== 'all') {
                    $cityInfo = CityService::getCityByKey($region);
                    if ($cityInfo && isset($cityInfo['id'])) {
                        // Фильтруем по region_id или по raw_data
                        $query->where(function($q) use ($region, $cityInfo) {
                            $q->whereHas('region', function($subQ) use ($region) {
                                $subQ->where('code', $region);
                            })->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) = ?", [$cityInfo['id']]);
                        });
                    }
                }
                $total = $query->count();
                $data = $query->orderBy('created_at', 'desc')
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->get();
                break;
                
            case 'parkings':
                $query = Parking::query();
                if ($region !== 'all') {
                    $cityInfo = CityService::getCityByKey($region);
                    if ($cityInfo && isset($cityInfo['id'])) {
                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) = ?", [$cityInfo['id']]);
                    }
                }
                $total = $query->count();
                $data = $query->orderBy('created_at', 'desc')
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->get();
                break;
                
            case 'houses':
                $query = House::query();
                if ($region !== 'all') {
                    $cityInfo = CityService::getCityByKey($region);
                    if ($cityInfo && isset($cityInfo['id'])) {
                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) = ?", [$cityInfo['id']]);
                    }
                }
                $total = $query->count();
                $data = $query->orderBy('created_at', 'desc')
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->get();
                break;
                
            case 'plots':
                $query = Plot::query();
                if ($region !== 'all') {
                    $cityInfo = CityService::getCityByKey($region);
                    if ($cityInfo && isset($cityInfo['id'])) {
                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) = ?", [$cityInfo['id']]);
                    }
                }
                $total = $query->count();
                $data = $query->orderBy('created_at', 'desc')
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->get();
                break;
                
            case 'commercial':
                $query = Commercial::query();
                if ($region !== 'all') {
                    $cityInfo = CityService::getCityByKey($region);
                    if ($cityInfo && isset($cityInfo['id'])) {
                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) = ?", [$cityInfo['id']]);
                    }
                }
                $total = $query->count();
                $data = $query->orderBy('created_at', 'desc')
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->get();
                break;
                
            case 'contractors':
                $total = Contractor::count();
                $data = Contractor::orderBy('created_at', 'desc')
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->get();
                break;
        }
        
        // Пагинация
        $totalPages = ceil($total / $perPage);
        
        return view('trendagent.db', [
            'type' => $type,
            'region' => $region,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'data' => $data,
            'statistics' => $statistics,
            'regionStats' => $regionStats,
            'regions' => $regions,
            'allCities' => CityService::getAllCities(),
        ]);
    }
}
