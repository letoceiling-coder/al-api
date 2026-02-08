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
echo "🔍 АНАЛИЗ API ЭНДПОИНТОВ: КВАРТИРЫ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ============================================================
// 1. СПИСОК КВАРТИР (1-я страница)
// ============================================================
echo "1️⃣ СПИСОК КВАРТИР (1-я страница)\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://api.trendagent.ru/v4_29/apartments/search/";
    $response = $client->get($url, [
        'query' => [
            'sort' => 'price',
            'sort_order' => 'asc',
            'count' => 50,
            'offset' => 0,
            'auth_token' => $token,
            'city' => $cityId,
            'lang' => 'ru',
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    
    echo "✅ Статус: " . $response->getStatusCode() . "\n";
    echo "📊 Структура ответа:\n";
    echo "   Ключи верхнего уровня: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['data'])) {
        echo "   Количество квартир: " . count($data['data']) . "\n";
        echo "   Total: " . ($data['total'] ?? 'N/A') . "\n";
        
        if (!empty($data['data'][0])) {
            $apt = $data['data'][0];
            echo "\n   📦 Первая квартира:\n";
            echo "      _id: " . ($apt['_id'] ?? 'N/A') . "\n";
            echo "      number: " . ($apt['number'] ?? 'N/A') . "\n";
            echo "      area: " . ($apt['area'] ?? 'N/A') . "\n";
            echo "      price: " . ($apt['price'] ?? 'N/A') . "\n";
            echo "      Доступные ключи: " . implode(', ', array_keys($apt)) . "\n";
        }
    }
    
    // Сохраняем пример
    file_put_contents('apartments_list_page1.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n   💾 Сохранено: apartments_list_page1.json\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 2. СПИСОК КВАРТИР (2-я страница)
// ============================================================
echo "2️⃣ СПИСОК КВАРТИР (2-я страница, offset=50)\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://api.trendagent.ru/v4_29/apartments/search/";
    $response = $client->get($url, [
        'query' => [
            'sort' => 'price',
            'sort_order' => 'asc',
            'count' => 50,
            'offset' => 50,
            'auth_token' => $token,
            'city' => $cityId,
            'lang' => 'ru',
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    
    echo "✅ Статус: " . $response->getStatusCode() . "\n";
    echo "   Количество квартир: " . (isset($data['data']) ? count($data['data']) : 0) . "\n";
    
    if (!empty($data['data'][0])) {
        $apt = $data['data'][0];
        echo "   Первая квартира на 2-й странице: " . ($apt['number'] ?? 'N/A') . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 3. ПЛАНИРОВКИ КВАРТИР (count=30)
// ============================================================
echo "3️⃣ ПЛАНИРОВКИ КВАРТИР (count=30)\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://api.trendagent.ru/v4_29/apartments/search/";
    $response = $client->get($url, [
        'query' => [
            'sort' => 'price',
            'sort_order' => 'asc',
            'count' => 30,
            'offset' => 0,
            'auth_token' => $token,
            'city' => $cityId,
            'lang' => 'ru',
        ],
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    
    echo "✅ Статус: " . $response->getStatusCode() . "\n";
    echo "   Количество: " . (isset($data['data']) ? count($data['data']) : 0) . "\n";
    
    if (!empty($data['data'][0])) {
        $apt = $data['data'][0];
        echo "\n   📐 Планировки в первой квартире:\n";
        
        // Ищем поля связанные с планировками
        $planFields = ['plan', 'plan_image', 'plans', 'layout', 'floor_plan', 'individual_plan'];
        foreach ($planFields as $field) {
            if (isset($apt[$field])) {
                echo "      {$field}: " . (is_array($apt[$field]) ? '[array]' : $apt[$field]) . "\n";
            }
        }
        
        // Проверяем все ключи
        echo "\n   Все ключи квартиры:\n";
        foreach (array_keys($apt) as $key) {
            $value = $apt[$key];
            if (is_array($value)) {
                echo "      {$key}: [array with " . count($value) . " items]\n";
            } else {
                echo "      {$key}: " . (is_string($value) && strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
            }
        }
    }
    
    file_put_contents('apartments_plans.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n   💾 Сохранено: apartments_plans.json\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 4. СПИСОК КОМПЛЕКСОВ (ЖК)
// ============================================================
echo "4️⃣ СПИСОК КОМПЛЕКСОВ (ЖК)\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://api.trendagent.ru/v4_29/blocks/search/";
    $response = $client->get($url, [
        'query' => [
            'show_type' => 'list',
            'sort' => 'price',
            'sort_order' => 'asc',
            'count' => 20,
            'offset' => 0,
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
        echo "   Total: " . ($data['total'] ?? $data['blocks_count'] ?? 'N/A') . "\n";
        
        if (!empty($data['data'][0])) {
            $block = $data['data'][0];
            echo "\n   🏢 Первый комплекс:\n";
            echo "      _id: " . ($block['_id'] ?? 'N/A') . "\n";
            echo "      name: " . ($block['name'] ?? 'N/A') . "\n";
            echo "      guid: " . ($block['guid'] ?? 'N/A') . "\n";
            echo "      Доступные ключи: " . implode(', ', array_keys($block)) . "\n";
        }
    }
    
    file_put_contents('complexes_list.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n   💾 Сохранено: complexes_list.json\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 5. КАРТА КОМПЛЕКСОВ
// ============================================================
echo "5️⃣ КАРТА КОМПЛЕКСОВ (show_type=map)\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $url = "https://api.trendagent.ru/v4_29/blocks/search/";
    $response = $client->get($url, [
        'query' => [
            'show_type' => 'map',
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
        echo "   Количество точек на карте: " . count($data['data']) . "\n";
        
        if (!empty($data['data'][0])) {
            $point = $data['data'][0];
            echo "\n   📍 Первая точка:\n";
            echo "      Доступные ключи: " . implode(', ', array_keys($point)) . "\n";
            
            if (isset($point['location']) || isset($point['coordinates'])) {
                echo "      Координаты найдены!\n";
            }
        }
    }
    
    file_put_contents('complexes_map.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n   💾 Сохранено: complexes_map.json\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 6. ИНФОРМАЦИЯ ПО КЛИКУ НА КАРТЕ
// ============================================================
echo "6️⃣ ИНФОРМАЦИЯ ПО КЛИКУ НА КАРТЕ\n";
echo "───────────────────────────────────────────────────────────────\n";

// Сначала получим ID комплекса из списка
$blockId = null;
if (file_exists('complexes_list.json')) {
    $complexesData = json_decode(file_get_contents('complexes_list.json'), true);
    if (!empty($complexesData['data'][0]['_id'])) {
        $blockId = $complexesData['data'][0]['_id'];
    }
}

if ($blockId) {
    try {
        $url = "https://api.trendagent.ru/v4_29/blocks/{$blockId}/map/";
        $response = $client->get($url, [
            'query' => [
                'formating' => 'true',
                'auth_token' => $token,
                'city' => $cityId,
                'lang' => 'ru',
            ],
        ]);
        
        $data = json_decode($response->getBody()->getContents(), true);
        
        echo "✅ Статус: " . $response->getStatusCode() . "\n";
        echo "   Block ID: {$blockId}\n";
        echo "📊 Структура ответа:\n";
        echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
        
        file_put_contents('complex_map_click.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "\n   💾 Сохранено: complex_map_click.json\n";
        
    } catch (\Exception $e) {
        echo "❌ Ошибка: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  Не удалось получить ID комплекса для теста\n";
}

echo "\n";

// ============================================================
// ИТОГИ
// ============================================================
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ АНАЛИЗ ЗАВЕРШЕН\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n📁 Сохраненные файлы:\n";
echo "   - apartments_list_page1.json\n";
echo "   - apartments_plans.json\n";
echo "   - complexes_list.json\n";
echo "   - complexes_map.json\n";
echo "   - complex_map_click.json\n";
echo "\n💡 Следующий шаг: Анализ структуры данных в сохраненных файлах\n";
