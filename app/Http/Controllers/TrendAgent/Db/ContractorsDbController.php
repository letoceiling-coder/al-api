<?php

namespace App\Http\Controllers\TrendAgent\Db;

use App\Http\Controllers\Controller;
use App\Models\TrendAgent\ContractorProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractorsDbController extends Controller
{
    /**
     * POST /houseprojects или objects/list object_type=contractors.
     */
    public function index(Request $request): JsonResponse
    {
        $count = max(1, min(100, (int) $request->input('count', 20)));
        $page = max(1, (int) $request->input('page', 1));
        $offset = (int) $request->input('offset', 0);
        if ($page > 1 && $offset === 0) {
            $offset = ($page - 1) * $count;
        }

        $query = ContractorProject::query();

        $total = $query->count();
        $items = $query->offset($offset)->limit($count)->get();

        $objects = $items->map(fn ($p) => [
            '_id' => $p->external_id,
            'id' => $p->external_id,
            'guid' => $p->guid,
            'name' => $p->name,
            'min_price' => $p->min_price,
        ])->all();

        return response()->json([
            'success' => true,
            'total_count' => $total,
            'data' => [
                'objects' => $objects,
                'contractors_count' => $total,
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
     * POST /houseprojects/{id}.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $project = ContractorProject::with('contractor')->where('external_id', $id)->orWhere('guid', $id)->orWhere('id', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                '_id' => $project->external_id,
                'id' => $project->external_id,
                'guid' => $project->guid,
                'name' => $project->name,
                'description' => $project->description,
                'min_price' => $project->min_price,
            ],
        ]);
    }
}
