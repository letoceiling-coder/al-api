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
echo "🔍 АНАЛИЗ API ЭНДПОИНТОВ: ПАРКИНГИ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ============================================================
// 1. СПИСОК МАШИНОМЕСТ
// ============================================================
echo "1️⃣ СПИСОК МАШИНОМЕСТ\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://parkings-api.trendagent.ru/search/places/";
    $response = $client->get($url, [
        'query' => [
            'count' => 50,
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
    echo "📊 Структура ответа:\n";
    echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['data'])) {
        echo "   Количество машиномест: " . count($data['data']) . "\n";
        echo "   Total: " . ($data['total'] ?? $data['places_count'] ?? 'N/A') . "\n";
        
        if (!empty($data['data'][0])) {
            $place = $data['data'][0];
            echo "\n   🅿️ Первое машиноместо:\n";
            echo "      Доступные ключи: " . implode(', ', array_keys($place)) . "\n";
        }
    } elseif (isset($data['result'])) {
        echo "   Количество машиномест: " . count($data['result']) . "\n";
        echo "   Total: " . ($data['places_count'] ?? $data['total'] ?? 'N/A') . "\n";
        
        if (!empty($data['result'][0])) {
            $place = $data['result'][0];
            echo "\n   🅿️ Первое машиноместо:\n";
            echo "      Доступные ключи: " . implode(', ', array_keys($place)) . "\n";
        }
    }
    
    file_put_contents('parkings_places_list.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n   💾 Сохранено: parkings_places_list.json\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 2. СПИСОК ЖК С ПАРКИНГАМИ
// ============================================================
echo "2️⃣ СПИСОК ЖК С ПАРКИНГАМИ\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://parkings-api.trendagent.ru/search/blocks";
    $response = $client->get($url, [
        'query' => [
            'count' => 20,
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
    echo "📊 Структура ответа:\n";
    echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['data'])) {
        echo "   Количество ЖК: " . count($data['data']) . "\n";
        echo "   Total: " . ($data['total'] ?? $data['blocks_count'] ?? 'N/A') . "\n";
        
        if (!empty($data['data'][0])) {
            $block = $data['data'][0];
            echo "\n   🏢 Первый ЖК:\n";
            echo "      Доступные ключи: " . implode(', ', array_keys($block)) . "\n";
        }
    } elseif (isset($data['result'])) {
        echo "   Количество ЖК: " . count($data['result']) . "\n";
        echo "   Total: " . ($data['blocks_count'] ?? $data['total'] ?? 'N/A') . "\n";
        
        if (!empty($data['result'][0])) {
            $block = $data['result'][0];
            echo "\n   🏢 Первый ЖК:\n";
            echo "      Доступные ключи: " . implode(', ', array_keys($block)) . "\n";
        }
    }
    
    file_put_contents('parkings_blocks_list.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n   💾 Сохранено: parkings_blocks_list.json\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 3. КАРТА ПАРКИНГОВ
// ============================================================
echo "3️⃣ КАРТА ПАРКИНГОВ\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://parkings-api.trendagent.ru/search/pins";
    $response = $client->get($url, [
        'query' => [
            'city' => $cityId,
            'lang' => 'ru',
            'auth_token' => $token,
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    
    echo "✅ Статус: " . $response->getStatusCode() . "\n";
    echo "📊 Структура ответа:\n";
    echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['data'])) {
        echo "   Количество точек: " . count($data['data']) . "\n";
    } elseif (isset($data['result'])) {
        echo "   Количество точек: " . count($data['result']) . "\n";
    }
    
    file_put_contents('parkings_map.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n   💾 Сохранено: parkings_map.json\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 4. ИНФОРМАЦИЯ ПО КЛИКУ НА КАРТЕ
// ============================================================
echo "4️⃣ ИНФОРМАЦИЯ ПО КЛИКУ НА КАРТЕ\n";
echo "───────────────────────────────────────────────────────────────\n";

// Получаем ID блока из списка
$blockId = null;
if (file_exists('parkings_blocks_list.json')) {
    $blocksData = json_decode(file_get_contents('parkings_blocks_list.json'), true);
    if (isset($blocksData['data'][0]['_id'])) {
        $blockId = $blocksData['data'][0]['_id'];
    } elseif (isset($blocksData['result'][0]['_id'])) {
        $blockId = $blocksData['result'][0]['_id'];
    } elseif (isset($blocksData['data'][0]['id'])) {
        $blockId = $blocksData['data'][0]['id'];
    }
}

if ($blockId) {
    try {
        $url = "https://parkings-api.trendagent.ru/search/map/block/{$blockId}";
        $response = $client->get($url, [
            'query' => [
                'city' => $cityId,
                'lang' => 'ru',
                'auth_token' => $token,
            ],
        ]);
        
        $data = json_decode($response->getBody()->getContents(), true);
        
        echo "✅ Статус: " . $response->getStatusCode() . "\n";
        echo "   Block ID: {$blockId}\n";
        echo "📊 Структура ответа:\n";
        echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
        
        file_put_contents('parkings_map_click.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "\n   💾 Сохранено: parkings_map_click.json\n";
        
    } catch (\Exception $e) {
        echo "❌ Ошибка: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  Не удалось получить ID блока для теста\n";
}

echo "\n";

// ============================================================
// ИТОГИ
// ============================================================
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ АНАЛИЗ ЗАВЕРШЕН\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n📁 Сохраненные файлы:\n";
echo "   - parkings_places_list.json\n";
echo "   - parkings_blocks_list.json\n";
echo "   - parkings_map.json\n";
echo "   - parkings_map_click.json\n";
