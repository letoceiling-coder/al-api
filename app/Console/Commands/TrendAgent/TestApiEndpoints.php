<?php

namespace App\Console\Commands\TrendAgent;

use App\Services\TrendAgent\TrendSsoApiAuth;
use Illuminate\Console\Command;
use Exception;

class TestApiEndpoints extends Command
{
    protected $signature = 'trendagent:test-api';
    protected $description = 'Тестирование доступных API endpoints TrendAgent';

    private TrendSsoApiAuth $auth;

    public function handle(): int
    {
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("ТЕСТИРОВАНИЕ API ENDPOINTS TRENDAGENT");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->newLine();

        // Авторизация
        $this->info("🔐 Авторизация...");
        $this->auth = new TrendSsoApiAuth();
        
        $phone = env('TRENDAGENT_PHONE', '+79045393434');
        $password = env('TRENDAGENT_PASSWORD', 'nwBvh4q');
        
        try {
            $this->auth->authenticate($phone, $password);
            $this->info("✅ Авторизован!");
        } catch (Exception $e) {
            $this->error("❌ Ошибка авторизации: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        $this->newLine();

        // Тестовый ID комплекса
        $testBlockId = '58ffaff5724dd8336c2bf27a';

        // 1. Паркинги
        $this->testBlockParkings($testBlockId);
        
        // 2. Планировки
        $this->testBlockPlans($testBlockId);
        
        // 3. Дома (комплексы)
        $this->testHousesSearch();
        
        // 4. Участки
        $this->testPlotsSearch();
        
        // 5. Поселки
        $this->testVillagesSearch();
        
        // 6. Коммерция (комплексы)
        $complexId = $this->testCommercialSearch();
        
        // 7. Дома комплекса (новый метод)
        $this->testBlockHouses('5c4d7ddeb16ba3c73fbbb8f6'); // ID комплекса с домами
        
        // 8. Коммерческие помещения комплекса (новый метод)
        if ($complexId) {
            $this->testBlockCommercial($complexId);
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("ТЕСТИРОВАНИЕ ЗАВЕРШЕНО");
        $this->info("═══════════════════════════════════════════════════════════");

        return Command::SUCCESS;
    }

    private function testBlockParkings(string $blockId): void
    {
        $this->info("1️⃣  ПАРКИНГИ КОМПЛЕКСА");
        $this->info("───────────────────────────────────────────────────────────");
        
        try {
            $result = $this->auth->getBlockParkings($blockId);
            $count = $this->getDataCount($result);
            
            $this->info("✅ getBlockParkings: {$count} машиномест");
            
            if ($count > 0) {
                $first = $this->getFirstItem($result);
                $this->line("   Пример первого объекта:");
                $this->line("   Ключи: " . implode(', ', array_keys((array)$first)));
            }
        } catch (Exception $e) {
            $this->error("❌ getBlockParkings: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testBlockPlans(string $blockId): void
    {
        $this->info("2️⃣  ПЛАНИРОВКИ КОМПЛЕКСА");
        $this->info("───────────────────────────────────────────────────────────");
        
        try {
            $result = $this->auth->getBlockPlans($blockId);
            $count = $this->getDataCount($result);
            
            $this->info("✅ getBlockPlans: {$count} планировок");
            
            if ($count > 0) {
                $first = $this->getFirstItem($result);
                $this->line("   Пример первого объекта:");
                $this->line("   Ключи: " . implode(', ', array_keys((array)$first)));
            }
        } catch (Exception $e) {
            $this->error("❌ getBlockPlans: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testHousesSearch(): void
    {
        $this->info("3️⃣  ПОИСК ДОМОВ (комплексы)");
        $this->info("───────────────────────────────────────────────────────────");
        
        try {
            $result = $this->auth->getHousesSearch([
                'city' => '58c665588b6aa52311afa01b',
                'count' => 5,
                'offset' => 0
            ]);
            
            $count = $this->getDataCount($result);
            $this->info("✅ getHousesSearch: {$count} комплексов");
            
            if ($count > 0) {
                $first = $this->getFirstItem($result);
                $this->line("   Пример первого объекта:");
                $this->line("   Ключи: " . implode(', ', array_keys((array)$first)));
                
                if (isset($first['_id'])) {
                    $this->line("   ID комплекса для тестов: " . $first['_id']);
                }
            }
        } catch (Exception $e) {
            $this->error("❌ getHousesSearch: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testPlotsSearch(): void
    {
        $this->info("4️⃣  ПОИСК УЧАСТКОВ");
        $this->info("───────────────────────────────────────────────────────────");
        
        try {
            $result = $this->auth->getPlotsSearch([
                'city' => '58c665588b6aa52311afa01b',
                'count' => 5,
                'offset' => 0
            ]);
            
            $count = $this->getDataCount($result);
            $this->info("✅ getPlotsSearch: {$count} участков");
            
            if ($count > 0) {
                $first = $this->getFirstItem($result);
                $this->line("   Пример первого объекта:");
                $this->line("   Ключи: " . implode(', ', array_keys((array)$first)));
                
                if (isset($first['_id'])) {
                    $this->line("   ID участка для тестов: " . $first['_id']);
                }
            }
        } catch (Exception $e) {
            $this->error("❌ getPlotsSearch: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testVillagesSearch(): void
    {
        $this->info("5️⃣  ПОИСК ПОСЕЛКОВ");
        $this->info("───────────────────────────────────────────────────────────");
        
        try {
            $result = $this->auth->getVillagesSearch([
                'city' => '58c665588b6aa52311afa01b',
                'count' => 5,
                'offset' => 0
            ]);
            
            $count = $this->getDataCount($result);
            $this->info("✅ getVillagesSearch: {$count} поселков");
            
            if ($count > 0) {
                $first = $this->getFirstItem($result);
                $this->line("   Пример первого объекта:");
                $this->line("   Ключи: " . implode(', ', array_keys((array)$first)));
                
                if (isset($first['_id'])) {
                    $this->line("   ID поселка для тестов: " . $first['_id']);
                }
            }
        } catch (Exception $e) {
            $this->error("❌ getVillagesSearch: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testCommercialSearch(): ?string
    {
        $this->info("6️⃣  ПОИСК КОММЕРЦИИ (комплексы)");
        $this->info("───────────────────────────────────────────────────────────");
        
        try {
            $result = $this->auth->getCommercialSearch([
                'city' => '58c665588b6aa52311afa01b',
                'count' => 5,
                'offset' => 0
            ]);
            
            $count = $this->getDataCount($result);
            $this->info("✅ getCommercialSearch: {$count} комплексов");
            
            if ($count > 0) {
                $first = $this->getFirstItem($result);
                $this->line("   Пример первого объекта:");
                $this->line("   Ключи: " . implode(', ', array_keys((array)$first)));
                
                if (isset($first['_id'])) {
                    $this->line("   ID комплекса для тестов: " . $first['_id']);
                    $this->newLine();
                    return $first['_id'];
                }
            }
        } catch (Exception $e) {
            $this->error("❌ getCommercialSearch: " . $e->getMessage());
        }
        
        $this->newLine();
        return null;
    }

    private function testBlockHouses(string $blockId): void
    {
        $this->info("7️⃣  ДОМА КОМПЛЕКСА (новый метод)");
        $this->info("───────────────────────────────────────────────────────────");
        
        try {
            $result = $this->auth->getBlockHouses($blockId);
            $count = $this->getDataCount($result);
            
            $this->info("✅ getBlockHouses: {$count} домов");
            
            if ($count > 0) {
                $first = $this->getFirstItem($result);
                $this->line("   Пример первого объекта:");
                $this->line("   Ключи: " . implode(', ', array_keys((array)$first)));
            }
        } catch (Exception $e) {
            $this->error("❌ getBlockHouses: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testBlockCommercial(string $blockId): void
    {
        $this->info("8️⃣  КОММЕРЧЕСКИЕ ПОМЕЩЕНИЯ КОМПЛЕКСА (новый метод)");
        $this->info("───────────────────────────────────────────────────────────");
        
        try {
            $result = $this->auth->getBlockCommercial($blockId);
            $count = $this->getDataCount($result);
            
            $this->info("✅ getBlockCommercial: {$count} помещений");
            
            if ($count > 0) {
                $first = $this->getFirstItem($result);
                $this->line("   Пример первого объекта:");
                $this->line("   Ключи: " . implode(', ', array_keys((array)$first)));
            }
        } catch (Exception $e) {
            $this->error("❌ getBlockCommercial: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function getDataCount($result): int
    {
        if (!isset($result['data'])) {
            return 0;
        }
        
        if (is_array($result['data'])) {
            return count($result['data']);
        }
        
        return 0;
    }

    private function getFirstItem($result)
    {
        if (!isset($result['data']) || !is_array($result['data']) || empty($result['data'])) {
            return null;
        }
        
        return $result['data'][0];
    }
}
