<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\TrendAgent\TrendAgentApiClient;
use GuzzleHttp\Client;

$api = app(TrendAgentApiClient::class);
$api->authenticate();

$token = $api->getAuthToken();
$client = new Client();

$cityId = '58c665588b6aa52311afa01b'; // SPB

echo "═══════════════════════════════════════════════════════════════\n";
echo "🔍 АНАЛИЗ API ЭНДПОИНТОВ: ДОМА\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ============================================================
// 1. СПИСОК ДОМОВ (room=30,40)
// ============================================================
echo "1️⃣ СПИСОК ДОМОВ (room=30,40)\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://api.trendagent.ru/v4_29/apartments/search/";
    $response = $client->get($url, [
        'query' => [
            'sort' => 'price',
            'sort_order' => 'asc',
            'count' => 50,
            'room' => [30, 40], // 30=Коттеджи, 40=Таунхаусы
            'auth_token' => $token,
            'city' => $cityId,
            'lang' => 'ru',
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    
    echo "✅ Статус: " . $response->getStatusCode() . "\n";
    echo "📊 Структура ответа:\n";
    echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['data']['list'])) {
        echo "   Количество домов: " . count($data['data']['list']) . "\n";
        echo "   Total: " . ($data['data']['apartmentsCount'] ?? 'N/A') . "\n";
        
        if (!empty($data['data']['list'][0])) {
            $house = $data['data']['list'][0];
            echo "\n   🏠 Первый дом:\n";
            echo "      Доступные ключи: " . implode(', ', array_keys($house)) . "\n";
        }
    }
    
    file_put_contents('houses_list.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n   💾 Сохранено: houses_list.json\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 2. КОМПЛЕКСЫ ДОМОВ (room=30,40)
// ============================================================
echo "2️⃣ КОМПЛЕКСЫ ДОМОВ (room=30,40)\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://api.trendagent.ru/v4_29/blocks/search/";
    $response = $client->get($url, [
        'query' => [
            'show_type' => 'list',
            'sort' => 'price',
            'sort_order' => 'asc',
            'count' => 20,
            'room' => [30, 40],
            'auth_token' => $token,
            'city' => $cityId,
            'lang' => 'ru',
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    
    echo "✅ Статус: " . $response->getStatusCode() . "\n";
    echo "📊 Структура ответа:\n";
    echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['data'])) {
        echo "   Количество комплексов: " . count($data['data']) . "\n";
        echo "   Total: " . ($data['data']['blocksCount'] ?? 'N/A') . "\n";
    }
    
    file_put_contents('houses_blocks_list.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n   💾 Сохранено: houses_blocks_list.json\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 3. ПЛАНИРОВКИ ДОМОВ
// ============================================================
echo "3️⃣ ПЛАНИРОВКИ ДОМОВ (count=30)\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://api.trendagent.ru/v4_29/apartments/search/";
    $response = $client->get($url, [
        'query' => [
            'sort' => 'price',
            'sort_order' => 'asc',
            'count' => 30,
            'room' => [30, 40],
            'auth_token' => $token,
            'city' => $cityId,
            'lang' => 'ru',
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    
    echo "✅ Статус: " . $response->getStatusCode() . "\n";
    echo "   Количество: " . (isset($data['data']['list']) ? count($data['data']['list']) : 0) . "\n";
    
    if (!empty($data['data']['list'][0])) {
        $house = $data['data']['list'][0];
        echo "\n   📐 Планировки в первом доме:\n";
        
        if (isset($house['plan'])) {
            echo "      plan: " . json_encode($house['plan']) . "\n";
        }
    }
    
    file_put_contents('houses_plans.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n   💾 Сохранено: houses_plans.json\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ АНАЛИЗ ЗАВЕРШЕН\n";
echo "═══════════════════════════════════════════════════════════════\n";
