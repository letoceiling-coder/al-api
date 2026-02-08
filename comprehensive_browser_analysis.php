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
$results = [];

echo "═══════════════════════════════════════════════════════════════\n";
echo "🔍 КОМПЛЕКСНЫЙ АНАЛИЗ ВСЕХ ЭНДПОИНТОВ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ============================================================
// 1. КВАРТИРЫ: Поиск второго способа получения данных
// ============================================================
echo "1️⃣ КВАРТИРЫ: Поиск второго способа\n";
echo "───────────────────────────────────────────────────────────────\n";

$apartmentEndpoints = [
    // Способ 1 (известный)
    [
        'name' => 'Способ 1: Прямой API - Список квартир',
        'url' => 'https://api.trendagent.ru/v4_29/apartments/search/',
        'params' => ['sort' => 'price', 'sort_order' => 'asc', 'count' => 10, 'offset' => 0, 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    
    // Попытки найти способ 2
    [
        'name' => 'Попытка 1: api.siteaccess.ru - Список',
        'url' => 'https://api.siteaccess.ru/trendagent/apartments',
        'params' => ['city' => 'spb', 'count' => 10],
        'headers' => ['Authorization' => "Bearer {$token}"],
    ],
    [
        'name' => 'Попытка 2: api.siteaccess.ru - Список (с offset)',
        'url' => 'https://api.siteaccess.ru/trendagent/apartments',
        'params' => ['city' => 'spb', 'count' => 10, 'offset' => 0],
        'headers' => ['Authorization' => "Bearer {$token}"],
    ],
    [
        'name' => 'Попытка 3: api.siteaccess.ru - Детальная информация',
        'url' => 'https://api.siteaccess.ru/trendagent/apartments/63c5614728d3bcf2420860b1',
        'params' => ['guid' => 'dom-na-naberezhnoy-st'],
        'headers' => ['Authorization' => "Bearer {$token}"],
    ],
    [
        'name' => 'Попытка 4: api.siteaccess.ru - Объекты',
        'url' => 'https://api.siteaccess.ru/trendagent/objects/list',
        'params' => ['city' => 'spb'],
        'headers' => ['Authorization' => "Bearer {$token}"],
    ],
];

foreach ($apartmentEndpoints as $endpoint) {
    echo "Тест: {$endpoint['name']}\n";
    try {
        $options = ['query' => $endpoint['params']];
        if (isset($endpoint['headers'])) {
            $options['headers'] = $endpoint['headers'];
        }
        
        $response = $client->get($endpoint['url'], $options);
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        if (is_array($data) && !empty($data)) {
            echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
            echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
            $results['apartments'][] = [
                'name' => $endpoint['name'],
                'url' => $endpoint['url'],
                'status' => $response->getStatusCode(),
                'keys' => array_keys($data),
            ];
        } else {
            echo "   ⚠️  Статус: " . $response->getStatusCode() . " (HTML ответ)\n";
        }
    } catch (\Exception $e) {
        $code = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
        echo "   ❌ Ошибка: {$code}\n";
    }
    echo "\n";
}

// ============================================================
// 2. ПАРКИНГИ: Поиск второго способа
// ============================================================
echo "2️⃣ ПАРКИНГИ: Поиск второго способа\n";
echo "───────────────────────────────────────────────────────────────\n";

$parkingEndpoints = [
    [
        'name' => 'Способ 1: Прямой API - Машиноместа',
        'url' => 'https://parkings-api.trendagent.ru/search/places/',
        'params' => ['count' => 10, 'offset' => 0, 'sort' => 'price', 'sort_order' => 'asc', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    [
        'name' => 'Попытка: api.siteaccess.ru - Паркинги',
        'url' => 'https://api.siteaccess.ru/trendagent/parkings',
        'params' => ['city' => 'spb'],
        'headers' => ['Authorization' => "Bearer {$token}"],
    ],
];

foreach ($parkingEndpoints as $endpoint) {
    echo "Тест: {$endpoint['name']}\n";
    try {
        $options = ['query' => $endpoint['params']];
        if (isset($endpoint['headers'])) {
            $options['headers'] = $endpoint['headers'];
        }
        
        $response = $client->get($endpoint['url'], $options);
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        if (is_array($data) && !empty($data)) {
            echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
            echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
        } else {
            echo "   ⚠️  Статус: " . $response->getStatusCode() . "\n";
        }
    } catch (\Exception $e) {
        $code = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
        echo "   ❌ Ошибка: {$code}\n";
    }
    echo "\n";
}

// ============================================================
// 3. ДОМА: Поиск второго способа
// ============================================================
echo "3️⃣ ДОМА: Поиск второго способа\n";
echo "───────────────────────────────────────────────────────────────\n";

$houseEndpoints = [
    [
        'name' => 'Способ 1: Прямой API - Список домов',
        'url' => 'https://api.trendagent.ru/v4_29/apartments/search/',
        'params' => ['sort' => 'price', 'sort_order' => 'asc', 'count' => 10, 'room' => [30, 40], 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    [
        'name' => 'Попытка: api.siteaccess.ru - Дома',
        'url' => 'https://api.siteaccess.ru/trendagent/houses',
        'params' => ['city' => 'spb'],
        'headers' => ['Authorization' => "Bearer {$token}"],
    ],
];

foreach ($houseEndpoints as $endpoint) {
    echo "Тест: {$endpoint['name']}\n";
    try {
        $options = ['query' => $endpoint['params']];
        if (isset($endpoint['headers'])) {
            $options['headers'] = $endpoint['headers'];
        }
        
        $response = $client->get($endpoint['url'], $options);
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        if (is_array($data) && !empty($data)) {
            echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
            echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
        } else {
            echo "   ⚠️  Статус: " . $response->getStatusCode() . "\n";
        }
    } catch (\Exception $e) {
        $code = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
        echo "   ❌ Ошибка: {$code}\n";
    }
    echo "\n";
}

// ============================================================
// 4. УЧАСТКИ: Поиск второго способа
// ============================================================
echo "4️⃣ УЧАСТКИ: Поиск второго способа\n";
echo "───────────────────────────────────────────────────────────────\n";

$plotEndpoints = [
    [
        'name' => 'Способ 1: Прямой API - Список участков',
        'url' => 'https://house-api.trendagent.ru/v1/search/villages',
        'params' => ['count' => 10, 'offset' => 0, 'sort_order' => 'asc', 'sort_type' => 'price', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    [
        'name' => 'Попытка: api.siteaccess.ru - Участки',
        'url' => 'https://api.siteaccess.ru/trendagent/plots',
        'params' => ['city' => 'spb'],
        'headers' => ['Authorization' => "Bearer {$token}"],
    ],
];

foreach ($plotEndpoints as $endpoint) {
    echo "Тест: {$endpoint['name']}\n";
    try {
        $options = ['query' => $endpoint['params']];
        if (isset($endpoint['headers'])) {
            $options['headers'] = $endpoint['headers'];
        }
        
        $response = $client->get($endpoint['url'], $options);
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        if (is_array($data) && !empty($data)) {
            echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
            echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
        } else {
            echo "   ⚠️  Статус: " . $response->getStatusCode() . "\n";
        }
    } catch (\Exception $e) {
        $code = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
        echo "   ❌ Ошибка: {$code}\n";
    }
    echo "\n";
}

// ============================================================
// 5. КОММЕРЦИЯ: Поиск второго способа
// ============================================================
echo "5️⃣ КОММЕРЦИЯ: Поиск второго способа\n";
echo "───────────────────────────────────────────────────────────────\n";

$commercialEndpoints = [
    [
        'name' => 'Способ 1: Прямой API - Помещения',
        'url' => 'https://commerce-api.trendagent.ru/search/premises',
        'params' => ['count' => 10, 'offset' => 0, 'sort' => 'price', 'sort_order' => 'asc', 'city' => $cityId, 'lang' => 'ru'],
    ],
    [
        'name' => 'Попытка: api.siteaccess.ru - Коммерция',
        'url' => 'https://api.siteaccess.ru/trendagent/commercial',
        'params' => ['city' => 'spb'],
        'headers' => ['Authorization' => "Bearer {$token}"],
    ],
];

foreach ($commercialEndpoints as $endpoint) {
    echo "Тест: {$endpoint['name']}\n";
    try {
        $options = ['query' => $endpoint['params']];
        if (isset($endpoint['headers'])) {
            $options['headers'] = $endpoint['headers'];
        }
        
        $response = $client->get($endpoint['url'], $options);
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        if (is_array($data) && !empty($data)) {
            echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
            echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
        } else {
            echo "   ⚠️  Статус: " . $response->getStatusCode() . "\n";
        }
    } catch (\Exception $e) {
        $code = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
        echo "   ❌ Ошибка: {$code}\n";
    }
    echo "\n";
}

// ============================================================
// 6. ПРОЕКТЫ ДОМОВ: Поиск второго способа
// ============================================================
echo "6️⃣ ПРОЕКТЫ ДОМОВ: Поиск второго способа\n";
echo "───────────────────────────────────────────────────────────────\n";

$projectEndpoints = [
    [
        'name' => 'Способ 1: Прямой API - Проекты',
        'url' => 'https://house-api.trendagent.ru/v1/projects/search',
        'params' => ['count' => 10, 'offset' => 0, 'sort_order' => 'asc', 'sort_type' => 'price', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    [
        'name' => 'Попытка: api.siteaccess.ru - Проекты',
        'url' => 'https://api.siteaccess.ru/trendagent/projects',
        'params' => ['city' => 'spb'],
        'headers' => ['Authorization' => "Bearer {$token}"],
    ],
];

foreach ($projectEndpoints as $endpoint) {
    echo "Тест: {$endpoint['name']}\n";
    try {
        $options = ['query' => $endpoint['params']];
        if (isset($endpoint['headers'])) {
            $options['headers'] = $endpoint['headers'];
        }
        
        $response = $client->get($endpoint['url'], $options);
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        if (is_array($data) && !empty($data)) {
            echo "   ✅ Статус: " . $response->getStatusCode() . "\n";
            echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
        } else {
            echo "   ⚠️  Статус: " . $response->getStatusCode() . "\n";
        }
    } catch (\Exception $e) {
        $code = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
        echo "   ❌ Ошибка: {$code}\n";
    }
    echo "\n";
}

// ============================================================
// 7. ДЕТАЛЬНАЯ ИНФОРМАЦИЯ: Поиск эндпоинтов
// ============================================================
echo "7️⃣ ДЕТАЛЬНАЯ ИНФОРМАЦИЯ: Поиск эндпоинтов\n";
echo "───────────────────────────────────────────────────────────────\n";

$detailEndpoints = [
    // Квартира
    [
        'name' => 'Детальная информация о квартире (способ 1)',
        'url' => 'https://api.trendagent.ru/v4_29/apartments/63c5614728d3bcf2420860b1',
        'params' => ['auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    [
        'name' => 'Детальная информация о квартире (способ 2)',
        'url' => 'https://api.siteaccess.ru/trendagent/apartments/63c5614728d3bcf2420860b1',
        'params' => ['guid' => 'dom-na-naberezhnoy-st'],
        'headers' => ['Authorization' => "Bearer {$token}"],
    ],
    [
        'name' => 'Планировки квартиры',
        'url' => 'https://api.trendagent.ru/v4_29/checkerboard/63c50acc9a85d53360f63a76/apartments/',
        'params' => ['auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    [
        'name' => 'Поэтажный план',
        'url' => 'https://api.trendagent.ru/v4_29/checkerboard/63c50acc9a85d53360f63a76/',
        'params' => ['auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    
    // Дом
    [
        'name' => 'Детальная информация о доме',
        'url' => 'https://api.trendagent.ru/v4_29/apartments/64dcedbb77be5275aff41ef9',
        'params' => ['room' => [30, 40], 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    
    // Участок
    [
        'name' => 'Детальная информация об участке',
        'url' => 'https://house-api.trendagent.ru/v1/villages/692578d2a5e15b2a6c65fdbd',
        'params' => ['city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    
    // Коммерция
    [
        'name' => 'Детальная информация о помещении',
        'url' => 'https://commerce-api.trendagent.ru/premises/66d02665d5fa3023a711487c',
        'params' => ['city' => $cityId, 'lang' => 'ru'],
    ],
    
    // Проект дома
    [
        'name' => 'Детальная информация о проекте',
        'url' => 'https://house-api.trendagent.ru/v1/projects/dk177-kopiya',
        'params' => ['city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
];

foreach ($detailEndpoints as $endpoint) {
    echo "Тест: {$endpoint['name']}\n";
    try {
        $options = ['query' => $endpoint['params']];
        if (isset($endpoint['headers'])) {
            $options['headers'] = $endpoint['headers'];
        }
        
        $response = $client->get($endpoint['url'], $options);
        $status = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        if ($status === 200 && is_array($data) && !empty($data)) {
            echo "   ✅ Статус: {$status}\n";
            echo "   Ключи: " . implode(', ', array_keys($data)) . "\n";
            
            // Сохраняем успешные эндпоинты
            $filename = 'detail_' . str_replace([' ', '/', '?'], '_', $endpoint['name']) . '.json';
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "   💾 Сохранено: {$filename}\n";
        } else {
            echo "   ⚠️  Статус: {$status}\n";
        }
    } catch (\Exception $e) {
        $code = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
        echo "   ❌ Ошибка: {$code}\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ АНАЛИЗ ЗАВЕРШЕН\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Сохраняем результаты
file_put_contents('browser_analysis_results.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\n💾 Результаты сохранены: browser_analysis_results.json\n";
