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
echo "🔍 АНАЛИЗ ОСТАВШИХСЯ ТИПОВ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ============================================================
// УЧАСТКИ
// ============================================================
echo "🏞️ УЧАСТКИ\n";
echo "───────────────────────────────────────────────────────────────\n";

// 1. Список участков
echo "1. Список участков:\n";
try {
    $url = "https://house-api.trendagent.ru/v1/search/villages";
    $response = $client->get($url, [
        'query' => [
            'count' => 1,
            'offset' => 0,
            'sort_order' => 'asc',
            'sort_type' => 'price',
            'city' => $cityId,
            'lang' => 'ru',
            'auth_token' => $token,
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
    echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['data'])) {
        echo "   Количество: " . count($data['data']) . "\n";
        echo "   Total: " . ($data['total'] ?? $data['plotsCount'] ?? 'N/A') . "\n";
    }
    
    file_put_contents('plots_list.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
} catch (\Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
}

// 2. Список поселков
echo "\n2. Список поселков:\n";
try {
    $url = "https://house-api.trendagent.ru/v1/search/villages";
    $response = $client->get($url, [
        'query' => [
            'count' => 20,
            'offset' => 0,
            'sort_order' => 'asc',
            'sort_type' => 'price',
            'city' => $cityId,
            'lang' => 'ru',
            'auth_token' => $token,
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
    echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['data'])) {
        echo "   Количество поселков: " . count($data['data']) . "\n";
        echo "   Total: " . ($data['total'] ?? $data['villagesCount'] ?? 'N/A') . "\n";
    }
    
    file_put_contents('villages_list.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
} catch (\Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
}

// 3. Карта поселков
echo "\n3. Карта поселков:\n";
try {
    $url = "https://house-api.trendagent.ru/v1/search/map/villages/pins";
    $response = $client->get($url, [
        'query' => [
            'city' => $cityId,
            'lang' => 'ru',
            'auth_token' => $token,
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
    echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
    
    file_put_contents('villages_map.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
} catch (\Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// КОММЕРЦИЯ - ПЛАНИРОВКИ
// ============================================================
echo "🏢 КОММЕРЦИЯ - ПЛАНИРОВКИ\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://commerce-api.trendagent.ru/search/premisesPlan";
    $response = $client->get($url, [
        'query' => [
            'count' => 15,
            'number' => '',
            'offset' => 0,
            'sort' => 'price',
            'sort_order' => 'asc',
            'city' => $cityId,
            'lang' => 'ru',
            'auth_token' => $token,
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    echo "✅ Статус: " . $response->getStatusCode() . "\n";
    echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['data']) || isset($data['result'])) {
        $count = isset($data['data']) ? count($data['data']) : count($data['result']);
        echo "   Количество планировок: {$count}\n";
    }
    
    file_put_contents('commercial_plans.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// ПРОЕКТЫ ДОМОВ
// ============================================================
echo "🏗️ ПРОЕКТЫ ДОМОВ\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://house-api.trendagent.ru/v1/projects/search";
    $response = $client->get($url, [
        'query' => [
            'count' => 20,
            'offset' => 0,
            'sort_order' => 'asc',
            'sort_type' => 'price',
            'city' => $cityId,
            'lang' => 'ru',
            'auth_token' => $token,
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    echo "✅ Статус: " . $response->getStatusCode() . "\n";
    echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['data'])) {
        echo "   Количество проектов: " . count($data['data']) . "\n";
        echo "   Total: " . ($data['total'] ?? $data['projectsCount'] ?? 'N/A') . "\n";
    }
    
    file_put_contents('house_projects_list.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ АНАЛИЗ ЗАВЕРШЕН\n";
echo "═══════════════════════════════════════════════════════════════\n";
