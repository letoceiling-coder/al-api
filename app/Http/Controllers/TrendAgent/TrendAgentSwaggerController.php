<?php

namespace App\Http\Controllers\TrendAgent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="TrendAgent API",
 *     description="API для получения данных о недвижимости с сайта trendagent.ru",
 *     @OA\Contact(
 *         email="support@siteaccess.ru"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://api.siteaccess.ru/trendagent",
 *     description="Production API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="trendagent_auth",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     description="Bearer token: 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Аутентификация через TrendAgent SSO"
 * )
 *
 * @OA\Tag(
 *     name="Apartments",
 *     description="Операции с квартирами"
 * )
 *
 * @OA\Tag(
 *     name="Houses",
 *     description="Операции с домами (коттеджи, таунхаусы)"
 * )
 *
 * @OA\Tag(
 *     name="Plots",
 *     description="Операции с участками (поселки)"
 * )
 *
 * @OA\Tag(
 *     name="Parkings",
 *     description="Операции с паркингами"
 * )
 *
 * @OA\Tag(
 *     name="Commercial",
 *     description="Операции с коммерческой недвижимостью"
 * )
 *
 * @OA\Tag(
 *     name="House Projects",
 *     description="Операции с проектами домов"
 * )
 */
class TrendAgentSwaggerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/trendagent/swagger",
     *     summary="Swagger UI для TrendAgent API",
     *     tags={"Documentation"},
     *     @OA\Response(
     *         response=200,
     *         description="Swagger UI интерфейс"
     *     )
     * )
     */
    public function index()
    {
        return view('trendagent.swagger');
    }
}
