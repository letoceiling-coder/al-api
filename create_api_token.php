<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

// Find or create user
$user = User::where('email', 'admin@al-api.local')->first();

if (!$user) {
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@al-api.local',
        'password' => bcrypt('password123'),
    ]);
    echo "✅ User created: {$user->email}\n";
} else {
    echo "✅ User found: {$user->email}\n";
}

// Create token
$token = $user->createToken('API Token')->plainTextToken;

echo "\n==============================================\n";
echo "API Token Created Successfully!\n";
echo "==============================================\n\n";
echo "User: {$user->email}\n";
echo "Password: password123\n\n";
echo "Token:\n{$token}\n\n";
echo "Usage:\n";
echo "curl -H 'Authorization: Bearer {$token}' \\\n";
echo "  https://api.siteaccess.ru/api/test\n\n";
echo "==============================================\n";
