<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\TrendAgent\TrendAgentApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

$api = app(TrendAgentApiClient::class);
$api->authenticate();

$token = $api->getAuthToken();
$cookieJar = new CookieJar();

$client = new Client([
    'cookies' => $cookieJar,
    'verify' => false,
    'timeout' => 60,
    'allow_redirects' => true,
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Connection' => 'keep-alive',
        'Upgrade-Insecure-Requests' => '1',
        'Sec-Fetch-Dest' => 'document',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Site' => 'none',
        'Sec-Fetch-User' => '?1',
    ],
]);

$cityId = '58c665588b6aa52311afa01b';
$results = [];

echo "═══════════════════════════════════════════════════════════════\n";
echo "🌐 АВТОМАТИЧЕСКИЙ БРАУЗЕРНЫЙ АНАЛИЗ ВСЕХ 12 СТРАНИЦ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Функция для анализа HTML страницы
function analyzePage($url, $name, $client, $token, $cityId) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📄 {$name}\n";
    echo "🔗 URL: {$url}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $pageData = [
        'url' => $url,
        'name' => $name,
        'html_structure' => [],
        'api_endpoints' => [],
        'images' => [],
        'documents' => [],
        'filters' => [],
        'blocks' => [],
    ];
    
    try {
        // Получаем HTML страницы
        $response = $client->get($url);
        $html = $response->getBody()->getContents();
        $statusCode = $response->getStatusCode();
        
        echo "✅ Статус: {$statusCode}\n";
        echo "📏 Размер HTML: " . strlen($html) . " байт\n\n";
        
        // Создаем DOMDocument для парсинга
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // 1. Ищем все скрипты с API запросами
        echo "🔍 Поиск API эндпоинтов в JavaScript...\n";
        $scripts = $xpath->query('//script');
        foreach ($scripts as $script) {
            $content = $script->nodeValue;
            if (preg_match_all('/(https?:\/\/[^\s\'"<>]+(?:api|search|v4_29|commerce|parkings|house-api)[^\s\'"<>]*)/i', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $pageData['api_endpoints'][] = $match;
                    echo "   📍 Найден эндпоинт: {$match}\n";
                }
            }
        }
        
        // 2. Ищем все изображения
        echo "\n🖼️  Поиск изображений...\n";
        $images = $xpath->query('//img[@src]');
        $imageCount = 0;
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if (!empty($src) && !str_starts_with($src, 'data:')) {
                $pageData['images'][] = [
                    'src' => $src,
                    'alt' => $img->getAttribute('alt'),
                ];
                $imageCount++;
            }
        }
        echo "   Найдено изображений: {$imageCount}\n";
        
        // 3. Ищем все ссылки на документы
        echo "\n📄 Поиск документов...\n";
        $links = $xpath->query('//a[@href]');
        $docCount = 0;
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $text = trim($link->nodeValue);
            if (preg_match('/\.(pdf|doc|docx|xls|xlsx|zip|rar)$/i', $href)) {
                $pageData['documents'][] = [
                    'url' => $href,
                    'text' => $text,
                ];
                $docCount++;
            }
        }
        echo "   Найдено документов: {$docCount}\n";
        
        // 4. Ищем фильтры (input, select, checkbox)
        echo "\n🔧 Поиск фильтров...\n";
        $filters = [];
        
        // Input fields
        $inputs = $xpath->query('//input[@type="text" or @type="number" or @type="range"]');
        foreach ($inputs as $input) {
            $name = $input->getAttribute('name');
            $id = $input->getAttribute('id');
            $placeholder = $input->getAttribute('placeholder');
            if ($name || $id) {
                $filters[] = [
                    'type' => 'input',
                    'name' => $name ?: $id,
                    'placeholder' => $placeholder,
                ];
            }
        }
        
        // Select fields
        $selects = $xpath->query('//select[@name]');
        foreach ($selects as $select) {
            $name = $select->getAttribute('name');
            $options = [];
            $optionNodes = $xpath->query('.//option', $select);
            foreach ($optionNodes as $option) {
                $value = $option->getAttribute('value');
                $text = trim($option->nodeValue);
                if ($value) {
                    $options[] = ['value' => $value, 'text' => $text];
                }
            }
            $filters[] = [
                'type' => 'select',
                'name' => $name,
                'options' => $options,
            ];
        }
        
        // Checkboxes
        $checkboxes = $xpath->query('//input[@type="checkbox"][@name]');
        foreach ($checkboxes as $checkbox) {
            $name = $checkbox->getAttribute('name');
            $value = $checkbox->getAttribute('value');
            $filters[] = [
                'type' => 'checkbox',
                'name' => $name,
                'value' => $value,
            ];
        }
        
        $pageData['filters'] = $filters;
        echo "   Найдено фильтров: " . count($filters) . "\n";
        
        // 5. Ищем основные блоки данных
        echo "\n📦 Поиск блоков данных...\n";
        $blocks = [];
        
        // Ищем секции
        $sections = $xpath->query('//section[@id or @class]');
        foreach ($sections as $section) {
            $id = $section->getAttribute('id');
            $class = $section->getAttribute('class');
            if ($id || $class) {
                $blocks[] = [
                    'type' => 'section',
                    'id' => $id,
                    'class' => $class,
                ];
            }
        }
        
        // Ищем div с данными
        $dataDivs = $xpath->query('//div[contains(@class, "data") or contains(@class, "table") or contains(@class, "list")]');
        foreach ($dataDivs as $div) {
            $class = $div->getAttribute('class');
            if ($class) {
                $blocks[] = [
                    'type' => 'div',
                    'class' => $class,
                ];
            }
        }
        
        $pageData['blocks'] = $blocks;
        echo "   Найдено блоков: " . count($blocks) . "\n";
        
        // Сохраняем HTML для дальнейшего анализа
        $filename = 'html_' . preg_replace('/[^a-z0-9]/i', '_', $name) . '.html';
        file_put_contents($filename, $html);
        echo "\n💾 HTML сохранен: {$filename}\n";
        
        echo "\n✅ Анализ страницы завершен\n\n";
        
        return $pageData;
        
    } catch (\Exception $e) {
        echo "❌ Ошибка: " . $e->getMessage() . "\n\n";
        return $pageData;
    }
}

// Список всех 12 страниц для анализа
$pages = [
    // Квартиры
    [
        'url' => 'https://promo.trendagent.ru/sloboda',
        'name' => '1. Квартиры - Комплекс (promo)',
    ],
    [
        'url' => 'https://spb.trendagent.ru/object/dom-na-naberezhnoy-st/flat/63c5614728d3bcf2420860b1?sort=price&sort_order=asc&open=table&page=1&position=400',
        'name' => '2. Квартиры - Детальная страница квартиры',
    ],
    [
        'url' => 'https://spb.trendagent.ru/objects/table/',
        'name' => '3. Квартиры - Фильтры (table)',
    ],
    
    // Паркинги
    [
        'url' => 'https://spb.trendagent.ru/parkings/table/',
        'name' => '4. Паркинги - Фильтры (table)',
    ],
    
    // Дома
    [
        'url' => 'https://spb.trendagent.ru/houses/table/',
        'name' => '5. Дома - Фильтры (table)',
    ],
    [
        'url' => 'https://spb.trendagent.ru/object/belaya-dacha/flat/64dcedbb77be5275aff41ef9?apartments-room=30&apartments-room=40&sort=price&sort_order=asc&open=plans',
        'name' => '6. Дома - Детальная страница дома',
    ],
    
    // Участки
    [
        'url' => 'https://spb.trendagent.ru/village/lebyazhe/plot/692578d2a5e15b2a6c65fdbd/?open=table&sort=price&sort_order=asc',
        'name' => '7. Участки - Детальная страница участка',
    ],
    [
        'url' => 'https://spb.trendagent.ru/villages/plots',
        'name' => '8. Участки - Фильтры (plots)',
    ],
    
    // Коммерция
    [
        'url' => 'https://spb.trendagent.ru/commerce/table',
        'name' => '9. Коммерция - Фильтры (table)',
    ],
    [
        'url' => 'https://spb.trendagent.ru/commerce-premise/66d02665d5fa3023a711487c/?open=table&sort=price&sort_order=asc',
        'name' => '10. Коммерция - Детальная страница помещения',
    ],
    
    // Проекты домов
    [
        'url' => 'https://spb.trendagent.ru/houseprojects',
        'name' => '11. Проекты домов - Фильтры',
    ],
    [
        'url' => 'https://spb.trendagent.ru/houseproject/dk177-kopiya?prev_event_page=search_projects_listing_page',
        'name' => '12. Проекты домов - Детальная страница проекта',
    ],
];

// Анализируем каждую страницу
foreach ($pages as $page) {
    $pageData = analyzePage($page['url'], $page['name'], $client, $token, $cityId);
    $results[] = $pageData;
    
    // Небольшая задержка между запросами
    sleep(2);
}

// Сохраняем результаты
$resultsFile = 'browser_analysis_complete_results.json';
file_put_contents($resultsFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ АНАЛИЗ ВСЕХ 12 СТРАНИЦ ЗАВЕРШЕН\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "💾 Результаты сохранены: {$resultsFile}\n";
echo "📊 Проанализировано страниц: " . count($results) . "\n";

// Создаем сводный отчет
$summary = [
    'total_pages' => count($results),
    'total_api_endpoints' => 0,
    'total_images' => 0,
    'total_documents' => 0,
    'total_filters' => 0,
    'total_blocks' => 0,
    'unique_api_endpoints' => [],
];

foreach ($results as $page) {
    $summary['total_api_endpoints'] += count($page['api_endpoints']);
    $summary['total_images'] += count($page['images']);
    $summary['total_documents'] += count($page['documents']);
    $summary['total_filters'] += count($page['filters']);
    $summary['total_blocks'] += count($page['blocks']);
    
    foreach ($page['api_endpoints'] as $endpoint) {
        if (!in_array($endpoint, $summary['unique_api_endpoints'])) {
            $summary['unique_api_endpoints'][] = $endpoint;
        }
    }
}

$summaryFile = 'browser_analysis_summary.json';
file_put_contents($summaryFile, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "\n📈 СВОДНАЯ СТАТИСТИКА:\n";
echo "   - Всего страниц: {$summary['total_pages']}\n";
echo "   - Всего API эндпоинтов: {$summary['total_api_endpoints']}\n";
echo "   - Уникальных API эндпоинтов: " . count($summary['unique_api_endpoints']) . "\n";
echo "   - Всего изображений: {$summary['total_images']}\n";
echo "   - Всего документов: {$summary['total_documents']}\n";
echo "   - Всего фильтров: {$summary['total_filters']}\n";
echo "   - Всего блоков: {$summary['total_blocks']}\n";
echo "\n💾 Сводка сохранена: {$summaryFile}\n";
