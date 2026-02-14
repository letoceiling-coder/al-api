<?php

namespace App\Http\Controllers\TrendAgent\Db;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObjectsDbController extends Controller
{
    public function __construct(
        private ApartmentsDbController $apartmentsDb,
        private ParkingsDbController $parkingsDb,
        private HousesDbController $housesDb,
        private PlotsDbController $plotsDb,
        private CommercialDbController $commercialDb,
        private ContractorsDbController $contractorsDb,
    ) {}

    /**
     * POST /objects/list — универсальный список по object_type.
     */
    public function list(Request $request): JsonResponse
    {
        $objectType = $request->input('object_type')
            ?? (str_contains($request->path(), 'houseprojects') ? 'contractors' : 'blocks');

        return match ($objectType) {
            'blocks' => $this->apartmentsDb->listComplexes($request),
            'apartments' => $this->apartmentsDb->listApartments($request),
            'parking', 'parkings' => $this->parkingsDb->index($request),
            'houses' => $this->housesDb->index($request),
            'plots', 'villages' => $this->plotsDb->index($request),
            'commercial' => $this->commercialDb->index($request),
            'contractors' => $this->contractorsDb->index($request),
            default => $this->apartmentsDb->listComplexes($request),
        };
    }

}
