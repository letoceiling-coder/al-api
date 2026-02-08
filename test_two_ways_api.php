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
echo "🔍 ТЕСТИРОВАНИЕ ДВУХ СПОСОБОВ ПОЛУЧЕНИЯ ДАННЫХ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ============================================================
// КВАРТИРЫ: Способ 1 vs Способ 2
// ============================================================
echo "1️⃣ КВАРТИРЫ: Два способа\n";
echo "───────────────────────────────────────────────────────────────\n";

// Способ 1: Прямой API
echo "Способ 1: api.trendagent.ru\n";
try {
    $url = "https://api.trendagent.ru/v4_29/apartments/search/";
    $response = $client->get($url, [
        'query' => [
            'sort' => 'price',
            'sort_order' => 'asc',
            'count' => 10,
            'offset' => 0,
            'auth_token' => $token,
            'city' => $cityId,
            'lang' => 'ru',
        ],
    ]);
    
    $data1 = json_decode($response->getBody()->getContents(), true);
    echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
    echo "   Ключи: " . implode(', ', array_keys($data1)) . "\n";
    echo "   Количество: " . (isset($data1['data']['list']) ? count($data1['data']['list']) : 0) . "\n";
    
} catch (\Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
}

// Способ 2: Через прокси (api.siteaccess.ru)
echo "\nСпособ 2: api.siteaccess.ru/trendagent\n";
try {
    $url = "https://api.siteaccess.ru/trendagent/apartments";
    $response = $client->get($url, [
        'query' => [
            'city' => 'spb',
            'count' => 10,
            'offset' => 0,
        ],
        'headers' => [
            'Authorization' => "Bearer {$token}",
        ],
    ]);
    
    $body = $response->getBody()->getContents();
    $data2 = json_decode($body, true);
    echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
    
    if (is_array($data2)) {
        echo "   Ключи: " . implode(', ', array_keys($data2)) . "\n";
    } else {
        echo "   Ответ: " . substr($body, 0, 200) . "\n";
    }
    
    file_put_contents('apartments_way2.json', $body);
    echo "   💾 Сохранено: apartments_way2.json\n";
    
} catch (\Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
    echo "   ⚠️  Возможно, этот эндпоинт требует другой авторизации\n";
}

// Детальная информация о квартире (способ 2)
echo "\nДетальная информация (способ 2):\n";
try {
    $aptId = '63c5614728d3bcf2420860b1'; // Из примера пользователя
    $url = "https://api.siteaccess.ru/trendagent/apartments/{$aptId}";
    $response = $client->get($url, [
        'query' => [
            'guid' => 'dom-na-naberezhnoy-st',
        ],
        'headers' => [
            'Authorization' => "Bearer {$token}",
        ],
    ]);
    
    $body3 = $response->getBody()->getContents();
    $data3 = json_decode($body3, true);
    echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
    
    if (is_array($data3)) {
        echo "   Ключи: " . implode(', ', array_keys($data3)) . "\n";
    } else {
        echo "   Ответ: " . substr($body3, 0, 200) . "\n";
    }
    
    file_put_contents('apartment_detail_way2.json', $body3);
    echo "   💾 Сохранено: apartment_detail_way2.json\n";
    
} catch (\Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// ПРОЕКТЫ ДОМОВ: Полный тест
// ============================================================
echo "2️⃣ ПРОЕКТЫ ДОМОВ: Полный тест\n";
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
    echo "📊 Структура ответа:\n";
    echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['list'])) {
        echo "   Количество проектов: " . count($data['list']) . "\n";
        echo "   Total: " . ($data['total_count'] ?? 'N/A') . "\n";
        
        if (!empty($data['list'][0])) {
            $project = $data['list'][0];
            echo "\n   🏗️ Первый проект:\n";
            echo "      _id: " . ($project['_id'] ?? 'N/A') . "\n";
            echo "      name: " . (is_string($project['name'] ?? null) ? $project['name'] : 'N/A') . "\n";
            echo "      contractor_name: " . (is_string($project['contractor_name'] ?? null) ? $project['contractor_name'] : 'N/A') . "\n";
            echo "      price: " . (is_numeric($project['price'] ?? null) ? $project['price'] : 'N/A') . "\n";
            echo "      square_total: " . (is_numeric($project['square_total'] ?? null) ? $project['square_total'] : 'N/A') . "\n";
            echo "      Доступные ключи: " . implode(', ', array_keys($project)) . "\n";
        }
    }
    
    file_put_contents('house_projects_full.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n   💾 Сохранено: house_projects_full.json\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// ИТОГИ
// ============================================================
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ ТЕСТИРОВАНИЕ ЗАВЕРШЕНО\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n💡 Следующий шаг: Браузерный анализ для поиска всех способов\n";
