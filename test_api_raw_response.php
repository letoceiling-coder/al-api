<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TrendAgent\TrendSsoApiAuth;

$auth = new TrendSsoApiAuth();
$auth->authenticate();

echo "=== Raw Apartments API Response ===\n";
$result = $auth->getApartmentsSearch(['city' => '58c665588b6aa52311afa01b', 'count' => 1, 'offset' => 0]);
echo "Total from API: " . ($result['total'] ?? 'NULL') . "\n";
echo "Data count: " . count($result['data'] ?? []) . "\n";
echo "Result keys: " . implode(', ', array_keys($result)) . "\n";
if (isset($result['raw_response'])) {
    $raw = $result['raw_response'];
    echo "Raw response keys: " . implode(', ', array_keys($raw)) . "\n";
    echo "Raw response sample: " . json_encode(array_slice($raw, 0, 3), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}
echo "\n";

echo "=== Raw Commercial API Response ===\n";
$result = $auth->getCommercePremisesSearch(['city' => '58c665588b6aa52311afa01b', 'count' => 1, 'offset' => 0]);
echo "Total from API: " . ($result['total'] ?? 'NULL') . "\n";
echo "Data count: " . count($result['data'] ?? []) . "\n";
echo "Result keys: " . implode(', ', array_keys($result)) . "\n";
if (isset($result['raw_response'])) {
    $raw = $result['raw_response'];
    if (is_array($raw)) {
        echo "Raw response keys: " . implode(', ', array_keys($raw)) . "\n";
        echo "Raw response sample: " . json_encode(array_slice($raw, 0, 3), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    }
}
