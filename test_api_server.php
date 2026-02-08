<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TrendAgent\TrendAgentApiClient;

$api = new TrendAgentApiClient();
$api->authenticate();

echo "=== Testing Apartments API ===\n";
$result = $api->getApartments(['city' => 'spb', 'count' => 1, 'offset' => 0]);
echo "Total: " . ($result['total'] ?? 'NULL') . "\n";
echo "Data count: " . count($result['data'] ?? []) . "\n";
if (!empty($result['data'])) {
    echo "First item ID: " . ($result['data'][0]['id'] ?? $result['data'][0]['_id'] ?? 'N/A') . "\n";
}
echo "\n";

echo "=== Testing Houses API ===\n";
$result = $api->getHouses(['city' => 'spb', 'count' => 1, 'offset' => 0]);
echo "Total: " . ($result['total'] ?? 'NULL') . "\n";
echo "Data count: " . count($result['data'] ?? []) . "\n";
echo "\n";

echo "=== Testing Commercial API ===\n";
$result = $api->getCommercial(['city' => 'spb', 'count' => 1, 'offset' => 0]);
echo "Total: " . ($result['total'] ?? 'NULL') . "\n";
echo "Data count: " . count($result['data'] ?? []) . "\n";
