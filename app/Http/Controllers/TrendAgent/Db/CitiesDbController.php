<?php

namespace App\Http\Controllers\TrendAgent\Db;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrendAgent\CityResource;
use App\Services\TrendAgent\CityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitiesDbController extends Controller
{
    /**
     * GET /cities — список городов (регионов) для фильтра.
     * Формат контракта: { success, data: [{ id, name }] }.
     */
    public function index(Request $request): JsonResponse
    {
        $cities = CityService::getAllCities();

        $items = [];
        foreach ($cities as $city) {
            $items[] = [
                'id' => $city['id'],
                'name' => $city['name'],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
}
