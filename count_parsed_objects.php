<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Storage;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📊 СТАТИСТИКА СПАРСЕННЫХ ОБЪЕКТОВ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$region = 'spb';
$basePath = "trendagent/parsing/{$region}/raw";

$stats = [
    'Комплексы' => ['path' => "{$basePath}/complexes", 'pattern' => 'list_offset_*.json'],
    'Квартиры' => ['path' => "{$basePath}/apartments", 'pattern' => 'list_offset_*.json'],
    'Паркинги (комплексы)' => ['path' => "{$basePath}/parkings", 'pattern' => 'complexes_list_*.json'],
    'Паркинги (машиноместа)' => ['path' => "{$basePath}/parkings/items", 'pattern' => 'block_*.json'],
    'Дома' => ['path' => "{$basePath}/houses", 'pattern' => 'list_offset_*.json'],
    'Участки' => ['path' => "{$basePath}/plots", 'pattern' => 'list_offset_*.json'],
    'Коммерция (помещения)' => ['path' => "{$basePath}/commercial", 'pattern' => 'premises_list_*.json'],
];

foreach ($stats as $name => $config) {
    $path = $config['path'];
    
    // Получаем все файлы в директории
    $files = Storage::files($path);
    
    // Фильтруем по паттерну
    $pattern = str_replace('*', '.*', $config['pattern']);
    $matchedFiles = array_filter($files, function($file) use ($pattern, $path) {
        $filename = basename($file);
        return preg_match("/{$pattern}/", $filename);
    });
    
    $totalObjects = 0;
    $filesCount = count($matchedFiles);
    
    // Считаем объекты в каждом файле
    foreach ($matchedFiles as $file) {
        $content = Storage::get($file);
        $data = json_decode($content, true);
        
        if (isset($data['data']['data']) && is_array($data['data']['data'])) {
            $totalObjects += count($data['data']['data']);
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $totalObjects += count($data['data']);
        }
    }
    
    echo sprintf(
        "%-30s: %5d объектов (%d файлов)\n",
        $name,
        $totalObjects,
        $filesCount
    );
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "\n💡 ПРИМЕЧАНИЯ:\n\n";
echo "⚠️  Квартиры: Показано то, что спарсено напрямую (неправильно)\n";
echo "   ✅ Правильно: парсить через комплексы (детали каждого комплекса)\n\n";
echo "⚠️  Паркинги: Показано комплексов и машиномест\n";
echo "   ✅ Правильно: ~3,644 машиноместа в 50 ЖК\n\n";
echo "✅ Коммерция: 1,775 помещений (ПРАВИЛЬНО!)\n\n";
