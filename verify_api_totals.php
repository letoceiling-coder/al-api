<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TrendAgent\TrendAgentApiClient;

$api = new TrendAgentApiClient();
$api->authenticate();

echo "=== Проверка данных из API ===\n\n";

// Комплексы
echo "1. Комплексы (ЖК):\n";
$result = $api->getObjectsList('spb', 'complexes', 1, 0);
echo "   Total: " . ($result['total'] ?? 'NULL') . "\n";
echo "   Data count: " . count($result['data'] ?? []) . "\n\n";

// Квартиры
echo "2. Квартиры:\n";
$result = $api->getApartments(['city' => 'spb', 'count' => 1, 'offset' => 0]);
echo "   Total: " . ($result['total'] ?? 'NULL') . "\n";
echo "   Data count: " . count($result['data'] ?? []) . "\n\n";

// Паркинги
echo "3. Паркинги (машиноместа):\n";
$result = $api->getParkingPlacesList(['city' => 'spb', 'count' => 1, 'offset' => 0]);
echo "   Total: " . ($result['total'] ?? 'NULL') . "\n";
echo "   Data count: " . count($result['data'] ?? []) . "\n\n";

// Дома
echo "4. Дома:\n";
$result = $api->getHouses(['city' => 'spb', 'count' => 1, 'offset' => 0]);
echo "   Total: " . ($result['total'] ?? 'NULL') . "\n";
echo "   Data count: " . count($result['data'] ?? []) . "\n\n";

// Участки
echo "5. Участки:\n";
$result = $api->getPlots(['city' => 'spb', 'count' => 1, 'offset' => 0]);
echo "   Total: " . ($result['total'] ?? 'NULL') . "\n";
echo "   Data count: " . count($result['data'] ?? []) . "\n\n";

// Коммерция
echo "6. Коммерция (помещения):\n";
$result = $api->getCommercial(['city' => 'spb', 'count' => 1, 'offset' => 0]);
echo "   Total: " . ($result['total'] ?? 'NULL') . "\n";
echo "   Data count: " . count($result['data'] ?? []) . "\n\n";

// Подрядчики
echo "7. Подрядчики (проекты домов):\n";
$result = $api->getContractors(['city' => 'spb', 'count' => 1, 'offset' => 0]);
echo "   Total: " . ($result['total'] ?? 'NULL') . "\n";
echo "   Data count: " . count($result['data'] ?? []) . "\n\n";
