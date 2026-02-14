<?php

namespace Tests\Feature;

use App\Models\TrendAgent\Apartment;
use Database\Seeders\TrendAgentTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrendAgentDbApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['trendagent.data_source' => 'db']);
    }

    public function test_cities_returns_success_and_data(): void
    {
        $response = $this->getJson('/api/trendagent/v1/cities');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    }

    public function test_authenticate_returns_stub_success(): void
    {
        $response = $this->postJson('/api/trendagent/v1/authenticate', [
            'phone' => '+79999999999',
            'password' => 'test',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['authenticated' => true],
            ]);
    }

    public function test_apartments_list_returns_remote_style_format(): void
    {
        if (!$this->hasTrendAgentTables()) {
            $this->markTestSkipped('Требуется миграции TrendAgent');
        }
        $this->seed(TrendAgentTestSeeder::class);

        $response = $this->postJson('/api/trendagent/v1/apartments', [
            'city' => '58c665588b6aa52311afa01b',
            'count' => 20,
            'page' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'total_count',
                'data' => [
                    'objects',
                    'apartments_count',
                    'objects_count',
                ],
                'pagination' => [
                    'count',
                    'offset',
                    'page',
                    'has_more',
                ],
            ]);
    }

    public function test_apartment_detail_returns_remote_style_format(): void
    {
        if (!$this->hasTrendAgentTables()) {
            $this->markTestSkipped('Требуется миграции TrendAgent');
        }
        $this->seed(TrendAgentTestSeeder::class);

        $apartment = Apartment::with('complex')->first();
        if (!$apartment || !$apartment->complex) {
            $this->markTestSkipped('Требуется импортированная квартира для теста');
        }

        $response = $this->postJson("/api/trendagent/v1/apartments/{$apartment->complex->external_id}/flat/{$apartment->external_id}", []);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    '_id',
                    'number',
                    'floor',
                    'area_total',
                    'price_base',
                ],
            ]);
    }

    public function test_apartments_list_image_url_present(): void
    {
        if (!$this->hasTrendAgentTables()) {
            $this->markTestSkipped('Требуется миграции TrendAgent');
        }
        $this->seed(TrendAgentTestSeeder::class);

        $response = $this->postJson('/api/trendagent/v1/apartments', [
            'city' => '58c665588b6aa52311afa01b',
            'count' => 5,
            'page' => 1,
        ]);

        $response->assertStatus(200);
        $objects = $response->json('data.objects');
        if (!empty($objects)) {
            $first = $objects[0];
            $this->assertArrayHasKey('plan_image_url', $first);
            $this->assertArrayHasKey('plan_image', $first);
        }
    }

    public function test_apartments_list_filter_by_price(): void
    {
        if (!$this->hasTrendAgentTables()) {
            $this->markTestSkipped('Требуется миграции TrendAgent');
        }
        $this->seed(TrendAgentTestSeeder::class);

        $response = $this->postJson('/api/trendagent/v1/apartments', [
            'city' => '58c665588b6aa52311afa01b',
            'count' => 20,
            'price_from' => 1000000,
            'price_to' => 10000000,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $objects = $response->json('data.objects');
        foreach ($objects ?? [] as $obj) {
            $price = $obj['price_base'] ?? $obj['base_price'] ?? null;
            if ($price !== null) {
                $this->assertGreaterThanOrEqual(1000000, $price);
                $this->assertLessThanOrEqual(10000000, $price);
            }
        }
    }

    public function test_apartments_list_filter_by_rooms(): void
    {
        if (!$this->hasTrendAgentTables()) {
            $this->markTestSkipped('Требуется миграции TrendAgent');
        }
        $this->seed(TrendAgentTestSeeder::class);

        $response = $this->postJson('/api/trendagent/v1/apartments', [
            'city' => '58c665588b6aa52311afa01b',
            'count' => 20,
            'room' => [2],
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $objects = $response->json('data.objects');
        foreach ($objects ?? [] as $obj) {
            $this->assertEquals(2, $obj['rooms'] ?? null);
        }
    }

    private function hasTrendAgentTables(): bool
    {
        try {
            \DB::connection()->getPdo();
            return Schema::hasTable('trendagent_apartments');
        } catch (\Throwable) {
            return false;
        }
    }
}
