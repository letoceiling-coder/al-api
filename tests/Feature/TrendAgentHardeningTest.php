<?php

namespace Tests\Feature;

use App\Models\TrendAgent\Apartment;
use App\Models\TrendAgent\SyncRun;
use Database\Seeders\TrendAgentTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrendAgentHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['trendagent.data_source' => 'db']);
    }

    private function hasTrendAgentTables(): bool
    {
        try {
            return Schema::hasTable('trendagent_apartments') && Schema::hasTable('trendagent_sync_runs');
        } catch (\Throwable) {
            return false;
        }
    }

    private function ensureParsingPathExists(): bool
    {
        $path = storage_path('trendagent/parsing/spb/details/apartments');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $file = "{$path}/apt_test.json";
        if (!File::exists($file)) {
            File::put($file, json_encode([
                'data' => [
                    'id' => 'apt_test_gc',
                    'block_id' => '5f1234567890abcdef123456',
                    'price_base' => 1000000,
                ],
            ]));
        }
        return File::exists($file);
    }

    public function test_health_endpoint_returns_ok_and_counts(): void
    {
        $response = $this->getJson('/api/trendagent/v1/health');

        $response->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertJsonStructure([
                'ok',
                'last_sync_runs',
                'counts' => [
                    'apartments' => ['active', 'total'],
                    'complexes' => ['active', 'total'],
                    'parkings' => ['active', 'total'],
                ],
            ]);
    }

    public function test_sync_runs_written(): void
    {
        if (!$this->hasTrendAgentTables()) {
            $this->markTestSkipped('Требуется миграции TrendAgent');
        }
        if (!$this->ensureParsingPathExists()) {
            $this->markTestSkipped('Не удалось создать путь для импорта');
        }

        $this->seed(TrendAgentTestSeeder::class);
        $before = SyncRun::count();

        $this->assertEquals(0, $this->artisan('trendagent:import-data', [
            '--region' => 'spb',
            '--type' => 'apartments',
            '--dry-run' => true,
        ]));

        // Dry-run не создаёт SyncRun
        $this->assertSame($before, SyncRun::count());

        // Реальный импорт создаёт SyncRun
        $this->assertEquals(0, $this->artisan('trendagent:import-data', [
            '--region' => 'spb',
            '--type' => 'apartments',
        ]));

        $runs = SyncRun::where('type', 'apartments')->orderByDesc('started_at')->get();
        $this->assertGreaterThanOrEqual(1, $runs->count());
        $run = $runs->first();
        $this->assertContains($run->status, ['success', 'running']);
        $this->assertArrayHasKey('created_count', $run->toArray());
    }

    public function test_deactivate_missing(): void
    {
        if (!$this->hasTrendAgentTables()) {
            $this->markTestSkipped('Требуется миграции TrendAgent');
        }
        if (!$this->ensureParsingPathExists()) {
            $this->markTestSkipped('Не удалось создать путь для импорта');
        }

        $this->seed(TrendAgentTestSeeder::class);
        $apt = Apartment::where('external_id', '5fabcdef1234567890abcdef')->first();
        if (!$apt) {
            $this->markTestSkipped('Нет тестовой квартиры');
        }

        $apt->update(['is_active' => true, 'last_seen_at' => now()->subDays(10)]);
        $this->assertTrue($apt->fresh()->is_active);

        $this->assertEquals(0, $this->artisan('trendagent:import-data', [
            '--region' => 'spb',
            '--type' => 'apartments',
            '--deactivate-missing' => '1',
            '--missing-days' => '7',
        ]));

        $apt->refresh();
        $this->assertFalse($apt->is_active);
    }

    public function test_images_gc_dry_run(): void
    {
        if (!Schema::hasTable('trendagent_images')) {
            $this->markTestSkipped('Требуется таблица trendagent_images');
        }

        $exitCode = $this->artisan('trendagent:images:gc', [
            '--days' => 30,
            '--dry-run' => true,
        ]);
        $this->assertSame(0, $exitCode);
    }
}
