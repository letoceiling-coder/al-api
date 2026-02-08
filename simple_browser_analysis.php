<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\TrendAgent\TrendAgentApiClient;
use GuzzleHttp\Client;

$api = app(TrendAgentApiClient::class);
$api->authenticate();

$token = $api->getAuthToken();

echo "═══════════════════════════════════════════════════════════════\n";
echo "🌐 АНАЛИЗ API ЭНДПОИНТОВ ДЛЯ ВСЕХ 12 СТРАНИЦ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$client = new Client([
    'verify' => false,
    'timeout' => 30,
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept' => 'application/json, text/plain, */*',
    ],
]);

$cityId = '58c665588b6aa52311afa01b';
$allEndpoints = [];

// Список всех известных эндпоинтов для тестирования
$endpointsToTest = [
    // Квартиры
    [
        'category' => 'Квартиры',
        'name' => 'Список квартир',
        'url' => 'https://api.trendagent.ru/v4_29/apartments/search/',
        'params' => ['sort' => 'price', 'sort_order' => 'asc', 'count' => 5, 'offset' => 0, 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    [
        'category' => 'Квартиры',
        'name' => 'Детальная информация о квартире',
        'url' => 'https://api.trendagent.ru/v4_29/apartments/63c5614728d3bcf2420860b1',
        'params' => ['auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    [
        'category' => 'Квартиры',
        'name' => 'Планировки квартир',
        'url' => 'https://api.trendagent.ru/v4_29/checkerboard/63c50acc9a85d53360f63a76/apartments/',
        'params' => ['auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    [
        'category' => 'Квартиры',
        'name' => 'Список комплексов (list)',
        'url' => 'https://api.trendagent.ru/v4_29/blocks/search/',
        'params' => ['show_type' => 'list', 'count' => 5, 'offset' => 0, 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    [
        'category' => 'Квартиры',
        'name' => 'Карта комплексов (map)',
        'url' => 'https://api.trendagent.ru/v4_29/blocks/search/',
        'params' => ['show_type' => 'map', 'count' => 5, 'offset' => 0, 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    
    // Паркинги
    [
        'category' => 'Паркинги',
        'name' => 'Машиноместа',
        'url' => 'https://parkings-api.trendagent.ru/search/places/',
        'params' => ['count' => 5, 'offset' => 0, 'sort' => 'price', 'sort_order' => 'asc', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    [
        'category' => 'Паркинги',
        'name' => 'Блоки паркингов',
        'url' => 'https://parkings-api.trendagent.ru/search/blocks/',
        'params' => ['count' => 5, 'offset' => 0, 'sort' => 'price', 'sort_order' => 'asc', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    
    // Дома
    [
        'category' => 'Дома',
        'name' => 'Список домов',
        'url' => 'https://api.trendagent.ru/v4_29/apartments/search/',
        'params' => ['sort' => 'price', 'sort_order' => 'asc', 'count' => 5, 'room' => [30, 40], 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    [
        'category' => 'Дома',
        'name' => 'Детальная информация о доме',
        'url' => 'https://api.trendagent.ru/v4_29/apartments/64dcedbb77be5275aff41ef9',
        'params' => ['room' => [30, 40], 'auth_token' => $token, 'city' => $cityId, 'lang' => 'ru'],
    ],
    
    // Участки
    [
        'category' => 'Участки',
        'name' => 'Список поселков',
        'url' => 'https://house-api.trendagent.ru/v1/search/villages',
        'params' => ['count' => 5, 'offset' => 0, 'sort_order' => 'asc', 'sort_type' => 'price', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    [
        'category' => 'Участки',
        'name' => 'Детальная информация о поселке',
        'url' => 'https://house-api.trendagent.ru/v1/villages/692578d2a5e15b2a6c65fdbd',
        'params' => ['city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    
    // Коммерция
    [
        'category' => 'Коммерция',
        'name' => 'Помещения',
        'url' => 'https://commerce-api.trendagent.ru/search/premises',
        'params' => ['count' => 5, 'offset' => 0, 'sort' => 'price', 'sort_order' => 'asc', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    [
        'category' => 'Коммерция',
        'name' => 'Блоки коммерции',
        'url' => 'https://commerce.trendagent.ru/search/blocks',
        'params' => ['count' => 5, 'offset' => 0, 'sort' => 'price', 'sort_order' => 'asc', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    [
        'category' => 'Коммерция',
        'name' => 'Детальная информация о помещении',
        'url' => 'https://commerce-api.trendagent.ru/premises/66d02665d5fa3023a711487c',
        'params' => ['city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    
    // Проекты домов
    [
        'category' => 'Проекты домов',
        'name' => 'Список проектов',
        'url' => 'https://house-api.trendagent.ru/v1/projects/search',
        'params' => ['count' => 5, 'offset' => 0, 'sort_order' => 'asc', 'sort_type' => 'price', 'city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
    [
        'category' => 'Проекты домов',
        'name' => 'Детальная информация о проекте',
        'url' => 'https://house-api.trendagent.ru/v1/projects/dk177-kopiya',
        'params' => ['city' => $cityId, 'lang' => 'ru', 'auth_token' => $token],
    ],
];

$results = [];

foreach ($endpointsToTest as $idx => $endpoint) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo ($idx + 1) . ". {$endpoint['category']} - {$endpoint['name']}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "URL: {$endpoint['url']}\n";
    
    try {
        $response = $client->get($endpoint['url'], ['query' => $endpoint['params']]);
        $status = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        if ($status === 200 && is_array($data)) {
            echo "✅ Статус: {$status}\n";
            echo "📊 Ключи: " . implode(', ', array_keys($data)) . "\n";
            
            if (isset($data['results']) && is_array($data['results'])) {
                echo "📦 Результатов: " . count($data['results']) . "\n";
            }
            
            // Сохраняем успешный результат
            $filename = 'api_' . preg_replace('/[^a-z0-9]/i', '_', $endpoint['category'] . '_' . $endpoint['name']) . '.json';
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "💾 Сохранено: {$filename}\n";
            
            $results[] = [
                'category' => $endpoint['category'],
                'name' => $endpoint['name'],
                'url' => $endpoint['url'],
                'status' => $status,
                'success' => true,
                'keys' => array_keys($data),
            ];
        } else {
            echo "⚠️  Статус: {$status} (не JSON)\n";
            $results[] = [
                'category' => $endpoint['category'],
                'name' => $endpoint['name'],
                'url' => $endpoint['url'],
                'status' => $status,
                'success' => false,
            ];
        }
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
        echo "❌ Ошибка: {$status}\n";
        $results[] = [
            'category' => $endpoint['category'],
            'name' => $endpoint['name'],
            'url' => $endpoint['url'],
            'status' => $status,
            'success' => false,
            'error' => $e->getMessage(),
        ];
    } catch (\Exception $e) {
        echo "❌ Ошибка: " . $e->getMessage() . "\n";
        $results[] = [
            'category' => $endpoint['category'],
            'name' => $endpoint['name'],
            'url' => $endpoint['url'],
            'status' => 'error',
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
    
    echo "\n";
    sleep(1); // Небольшая задержка между запросами
}

// Сохраняем сводный отчет
file_put_contents('api_analysis_results.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ АНАЛИЗ ЗАВЕРШЕН\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 Протестировано эндпоинтов: " . count($results) . "\n";
echo "✅ Успешных: " . count(array_filter($results, fn($r) => $r['success'])) . "\n";
echo "❌ Ошибок: " . count(array_filter($results, fn($r) => !$r['success'])) . "\n";
echo "💾 Результаты сохранены: api_analysis_results.json\n";
