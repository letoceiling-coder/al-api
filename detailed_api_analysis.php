<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\TrendAgent\TrendAgentApiClient;
use GuzzleHttp\Client;

$api = app(TrendAgentApiClient::class);
$api->authenticate();

$token = $api->getAuthToken();
$client = new Client([
    'verify' => false,
    'timeout' => 30,
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept' => 'application/json, text/plain, */*',
    ],
]);

$cityId = '58c665588b6aa52311afa01b'; // SPB
$results = [];

echo "═══════════════════════════════════════════════════════════════\n";
echo "🔍 ДЕТАЛЬНЫЙ АНАЛИЗ ВСЕХ ЭНДПОИНТОВ И СТРУКТУР ДАННЫХ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Функция для тестирования эндпоинта
function testEndpoint($name, $url, $params, $headers = [], $client) {
    echo "  📍 {$name}\n";
    echo "     URL: {$url}\n";
    
    try {
        $options = ['query' => $params];
        if (!empty($headers)) {
            $options['headers'] = $headers;
        }
        
        $response = $client->get($url, $options);
        $status = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        
        // Пытаемся декодировать JSON
        $data = json_decode($body, true);
        
        if ($status === 200) {
            if (is_array($data) && !empty($data)) {
                echo "     ✅ Статус: {$status} (JSON)\n";
                echo "     📊 Ключи верхнего уровня: " . implode(', ', array_keys($data)) . "\n";
                
                // Анализируем структуру
                if (isset($data['results']) && is_array($data['results'])) {
                    echo "     📦 Количество результатов: " . count($data['results']) . "\n";
                    if (!empty($data['results'])) {
                        $first = $data['results'][0];
                        echo "     🔑 Ключи первого элемента: " . implode(', ', array_keys($first)) . "\n";
                    }
                }
                
                return ['success' => true, 'data' => $data, 'status' => $status];
            } else {
                echo "     ⚠️  Статус: {$status} (HTML или пустой)\n";
                echo "     📄 Первые 200 символов: " . substr($body, 0, 200) . "\n";
                return ['success' => false, 'body' => $body, 'status' => $status];
            }
        } else {
            echo "     ⚠️  Статус: {$status}\n";
            return ['success' => false, 'status' => $status, 'body' => $body];
        }
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
        $body = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
        echo "     ❌ Ошибка: {$status}\n";
        if ($status === 401) {
            echo "     🔐 Требуется авторизация\n";
        } elseif ($status === 404) {
            echo "     🔍 Эндпоинт не найден\n";
        }
        return ['success' => false, 'status' => $status, 'error' => $e->getMessage()];
    } catch (\Exception $e) {
        echo "     ❌ Ошибка: " . $e->getMessage() . "\n";
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ============================================================
// 1. КВАРТИРЫ
// ============================================================
echo "1️⃣ КВАРТИРЫ: Анализ всех способов получения данных\n";
echo "───────────────────────────────────────────────────────────────\n";

// Способ 1: Прямой API
$result1 = testEndpoint(
    'Способ 1: Прямой API - Список квартир',
    'https://api.trendagent.ru/v4_29/apartments/search/',
    ['sort' => 'price', 'sort_order' => 'asc', 'count' => 5, 'offset' => 0, 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    [],
    $client
);

// Попытка найти способ 2 через api.siteaccess.ru
$result2 = testEndpoint(
    'Попытка 2: api.siteaccess.ru - Список квартир',
    'https://api.siteaccess.ru/trendagent/apartments',
    ['city' => 'spb', 'count' => 5],
    ['Authorization' => "Bearer {$token}"],
    $client
);

// Детальная информация о квартире
$result3 = testEndpoint(
    'Детальная информация о квартире',
    'https://api.trendagent.ru/v4_29/apartments/63c5614728d3bcf2420860b1',
    ['auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    [],
    $client
);

// Планировки
$result4 = testEndpoint(
    'Планировки квартир (checkerboard)',
    'https://api.trendagent.ru/v4_29/checkerboard/63c50acc9a85d53360f63a76/apartments/',
    ['auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    [],
    $client
);

echo "\n";

// ============================================================
// 2. ПАРКИНГИ
// ============================================================
echo "2️⃣ ПАРКИНГИ: Анализ всех способов получения данных\n";
echo "───────────────────────────────────────────────────────────────\n";

$result5 = testEndpoint(
    'Способ 1: Прямой API - Машиноместа',
    'https://parkings-api.trendagent.ru/search/places/',
    ['count' => 5, 'offset' => 0, 'sort' => 'price', 'sort_order' => 'asc', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    [],
    $client
);

$result6 = testEndpoint(
    'Способ 1: Прямой API - Блоки паркингов',
    'https://parkings-api.trendagent.ru/search/blocks/',
    ['count' => 5, 'offset' => 0, 'sort' => 'price', 'sort_order' => 'asc', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    [],
    $client
);

echo "\n";

// ============================================================
// 3. ДОМА
// ============================================================
echo "3️⃣ ДОМА: Анализ всех способов получения данных\n";
echo "───────────────────────────────────────────────────────────────\n";

$result7 = testEndpoint(
    'Способ 1: Прямой API - Список домов (room=30,40)',
    'https://api.trendagent.ru/v4_29/apartments/search/',
    ['sort' => 'price', 'sort_order' => 'asc', 'count' => 5, 'room' => [30, 40], 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    [],
    $client
);

$result8 = testEndpoint(
    'Детальная информация о доме',
    'https://api.trendagent.ru/v4_29/apartments/64dcedbb77be5275aff41ef9',
    ['room' => [30, 40], 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    [],
    $client
);

echo "\n";

// ============================================================
// 4. УЧАСТКИ
// ============================================================
echo "4️⃣ УЧАСТКИ: Анализ всех способов получения данных\n";
echo "───────────────────────────────────────────────────────────────\n";

$result9 = testEndpoint(
    'Способ 1: Прямой API - Список поселков',
    'https://house-api.trendagent.ru/v1/search/villages',
    ['count' => 5, 'offset' => 0, 'sort_order' => 'asc', 'sort_type' => 'price', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    [],
    $client
);

$result10 = testEndpoint(
    'Детальная информация о поселке',
    'https://house-api.trendagent.ru/v1/villages/692578d2a5e15b2a6c65fdbd',
    ['city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    [],
    $client
);

echo "\n";

// ============================================================
// 5. КОММЕРЦИЯ
// ============================================================
echo "5️⃣ КОММЕРЦИЯ: Анализ всех способов получения данных\n";
echo "───────────────────────────────────────────────────────────────\n";

$result11 = testEndpoint(
    'Способ 1: Прямой API - Помещения',
    'https://commerce-api.trendagent.ru/search/premises',
    ['count' => 5, 'offset' => 0, 'sort' => 'price', 'sort_order' => 'asc', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    [],
    $client
);

$result12 = testEndpoint(
    'Способ 1: Прямой API - Блоки коммерции',
    'https://commerce.trendagent.ru/search/blocks',
    ['count' => 5, 'offset' => 0, 'sort' => 'price', 'sort_order' => 'asc', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    [],
    $client
);

$result13 = testEndpoint(
    'Детальная информация о помещении',
    'https://commerce-api.trendagent.ru/premises/66d02665d5fa3023a711487c',
    ['city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    [],
    $client
);

echo "\n";

// ============================================================
// 6. ПРОЕКТЫ ДОМОВ
// ============================================================
echo "6️⃣ ПРОЕКТЫ ДОМОВ: Анализ всех способов получения данных\n";
echo "───────────────────────────────────────────────────────────────\n";

$result14 = testEndpoint(
    'Способ 1: Прямой API - Проекты',
    'https://house-api.trendagent.ru/v1/projects/search',
    ['count' => 5, 'offset' => 0, 'sort_order' => 'asc', 'sort_type' => 'price', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    [],
    $client
);

$result15 = testEndpoint(
    'Детальная информация о проекте',
    'https://house-api.trendagent.ru/v1/projects/dk177-kopiya',
    ['city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    [],
    $client
);

echo "\n";

// ============================================================
// 7. КОМПЛЕКСЫ
// ============================================================
echo "7️⃣ КОМПЛЕКСЫ: Анализ всех способов получения данных\n";
echo "───────────────────────────────────────────────────────────────\n";

$result16 = testEndpoint(
    'Способ 1: Прямой API - Список комплексов (list)',
    'https://api.trendagent.ru/v4_29/blocks/search/',
    ['show_type' => 'list', 'count' => 5, 'offset' => 0, 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    [],
    $client
);

$result17 = testEndpoint(
    'Способ 1: Прямой API - Карта комплексов (map)',
    'https://api.trendagent.ru/v4_29/blocks/search/',
    ['show_type' => 'map', 'count' => 5, 'offset' => 0, 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    [],
    $client
);

echo "\n";

// Сохраняем успешные результаты
$successfulResults = [];
foreach ([$result1, $result2, $result3, $result4, $result5, $result6, $result7, $result8, $result9, $result10, $result11, $result12, $result13, $result14, $result15, $result16, $result17] as $idx => $result) {
    if (isset($result['success']) && $result['success'] && isset($result['data'])) {
        $filename = 'analysis_result_' . ($idx + 1) . '.json';
        file_put_contents($filename, json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $successfulResults[] = $filename;
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ АНАЛИЗ ЗАВЕРШЕН\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "💾 Сохранено успешных результатов: " . count($successfulResults) . "\n";
if (!empty($successfulResults)) {
    echo "📁 Файлы:\n";
    foreach ($successfulResults as $file) {
        echo "   - {$file}\n";
    }
}
