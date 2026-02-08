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

class TrendAgentDbController extends Controller
{
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
        
        // Статистика по всем типам объектов
        $statistics = [
            'apartments' => Apartment::count(),
            'complexes' => Complex::count(),
            'parkings' => Parking::count(),
            'houses' => House::count(),
            'plots' => Plot::count(),
            'commercial' => Commercial::count(),
            'contractors' => Contractor::count(),
        ];
        
        // Статистика по регионам
        $regions = Region::all();
        $regionStats = [];
        foreach ($regions as $reg) {
            $cityInfo = CityService::getCityByKey($reg->code);
            if ($cityInfo && isset($cityInfo['id'])) {
                $count = Apartment::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) = ?", [$cityInfo['id']])
                    ->count();
                $regionStats[$reg->code] = [
                    'name' => $reg->name,
                    'count' => $count,
                ];
            }
        }
        
        // Получаем данные в зависимости от типа
        $data = [];
        $total = 0;
        
        switch ($type) {
            case 'apartments':
                $query = Apartment::query();
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
