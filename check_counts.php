<?php
$files = [0, 40, 80, 120, 160, 200, 240, 280, 320, 344];
foreach ($files as $offset) {
    $file = "/var/www/AL/storage/app/private/trendagent/parsing/spb/raw/apartments/list_offset_{$offset}.json";
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        $count = count($data['data']['data'] ?? []);
        echo "offset_{$offset}: {$count} items\n";
    }
}
