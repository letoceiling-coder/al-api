<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Regions: " . App\Models\TrendAgent\Region::count() . PHP_EOL;
echo "Complexes: " . App\Models\TrendAgent\Complex::count() . PHP_EOL;
echo "Apartments: " . App\Models\TrendAgent\Apartment::count() . PHP_EOL;
