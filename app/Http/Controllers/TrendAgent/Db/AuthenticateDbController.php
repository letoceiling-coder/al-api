<?php

namespace App\Http\Controllers\TrendAgent\Db;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticateDbController extends Controller
{
    /**
     * POST /authenticate — заглушка при DB-источнике.
     * UI ожидает { success, data: { authenticated: true } }.
     */
    public function authenticate(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'authenticated' => true,
                'auth_token' => null,
            ],
        ]);
    }
}
