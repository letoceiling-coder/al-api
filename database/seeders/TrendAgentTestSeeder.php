<?php

namespace Database\Seeders;

use App\Models\TrendAgent\Apartment;
use App\Models\TrendAgent\Complex;
use App\Models\TrendAgent\Region;
use Illuminate\Database\Seeder;

class TrendAgentTestSeeder extends Seeder
{
    public function run(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('trendagent_regions')) {
            return;
        }

        $region = Region::firstOrCreate(
            ['code' => 'spb'],
            ['name' => 'Санкт-Петербург', 'external_id' => '58c665588b6aa52311afa01b']
        );

        $complex = Complex::firstOrCreate(
            ['external_id' => '5f1234567890abcdef123456'],
            [
                'region_id' => $region->id,
                'guid' => 'test-complex',
                'name' => 'Тестовый ЖК',
                'address' => 'ул. Тестовая, 1',
                'min_price' => 5000000,
            ]
        );

        Apartment::firstOrCreate(
            ['external_id' => '5fabcdef1234567890abcdef'],
            [
                'complex_id' => $complex->id,
                'region_id' => $region->id,
                'number' => '101',
                'rooms' => 2,
                'floor' => 1,
                'area_total' => 50.5,
                'price_base' => 5500000,
                'plan_image_url' => 'https://example.com/plan.jpg',
            ]
        );
    }
}
