<?php

namespace App\Console\Commands\TrendAgent;

use App\Services\TrendAgent\TrendAgentApiClient;
use Illuminate\Console\Command;
use Exception;

class TestRegionFilter extends Command
{
    protected $signature = 'trendagent:test-region-filter 
                            {--type=houses : Тип объектов для теста}
                            {--region-guid=vsevolozhskiy : GUID района для теста}
                            {--region-id= : MongoID района для теста (_id)}';
    
    protected $description = 'Тестирование фильтрации API по районам (GUID vs MongoID)';

    private TrendAgentApiClient $apiClient;

    public function __construct(TrendAgentApiClient $apiClient)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
    }

    public function handle(): int
    {
        $type = $this->option('type');
        $regionGuid = $this->option('region-guid');
        $regionId = $this->option('region-id');
        
        $this->info("🧪 Тестирование фильтрации по району");
        $this->info("📦 Тип: {$type}");
        if ($regionGuid) {
            $this->info("📍 Район (GUID): {$regionGuid}");
        }
        if ($regionId) {
            $this->info("📍 Район (MongoID): {$regionId}");
        }
        $this->newLine();
        
        try {
            // Тест 1: Без фильтра по району
            $this->info("=== Тест 1: Без фильтра по району ===");
            $result1 = $this->testWithoutRegion($type);
            $this->displayResults($result1, "Без фильтра");
            $this->newLine();
            
            // Тест 2: С фильтром по району (GUID)
            if ($regionGuid) {
                $this->info("=== Тест 2: С фильтром по району GUID ({$regionGuid}) ===");
                $result2 = $this->testWithRegion($type, ['region' => $regionGuid]);
                $this->displayResults($result2, "С GUID фильтром '{$regionGuid}'");
                $this->analyzeRegions($result2['items'], "С GUID '{$regionGuid}'");
                $this->newLine();
            }
            
            // Тест 3: С фильтром по району (MongoID)
            if ($regionId) {
                $this->info("=== Тест 3: С фильтром по району MongoID ({$regionId}) ===");
                $result3 = $this->testWithRegion($type, ['region' => $regionId]);
                $this->displayResults($result3, "С MongoID фильтром '{$regionId}'");
                $this->analyzeRegions($result3['items'], "С MongoID '{$regionId}'");
                $this->newLine();
            }
            
            // Тест 4: Анализ всех данных
            $this->info("=== Тест 4: Анализ районов в полученных данных ===");
            $this->analyzeRegions($result1['items'], "Без фильтра");
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error("❌ Ошибка: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function testWithoutRegion(string $type): array
    {
        try {
            $params = [
                'city' => 'spb',
                'count' => 40,
                'offset' => 0,
            ];
            
            $method = 'get' . ucfirst($type);
            $data = $this->apiClient->$method($params);
            
            return [
                'success' => true,
                'items' => $data['data'] ?? [],
                'total' => $data['total'] ?? count($data['data'] ?? []),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'items' => [],
                'total' => 0,
            ];
        }
    }

    private function testWithRegion(string $type, array $filterParams): array
    {
        try {
            $params = array_merge([
                'city' => 'spb',
                'count' => 40,
                'offset' => 0,
            ], $filterParams);  // Добавляем фильтры
            
            $method = 'get' . ucfirst($type);
            $data = $this->apiClient->$method($params);
            
            return [
                'success' => true,
                'items' => $data['data'] ?? [],
                'total' => $data['total'] ?? count($data['data'] ?? []),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'items' => [],
                'total' => 0,
            ];
        }
    }

    private function displayResults(array $result, string $label): void
    {
        if ($result['success']) {
            $this->info("Получено объектов: " . count($result['items']));
            $this->info("Total в ответе: " . $result['total']);
            
            if (!empty($result['items'])) {
                $firstItem = $result['items'][0];
                $this->comment("Первый объект: " . ($firstItem['name'] ?? 'N/A'));
                if (isset($firstItem['_id'])) {
                    $this->comment("ID: " . $firstItem['_id']);
                }
            }
        } else {
            $this->error("Ошибка: " . ($result['error'] ?? 'Unknown'));
        }
    }

    private function analyzeRegions(array $items, string $label): void
    {
        $regions = [];
        
        foreach ($items as $item) {
            if (isset($item['region']['guid'])) {
                $guid = $item['region']['guid'];
                $name = $item['region']['name'] ?? $guid;
                
                if (!isset($regions[$guid])) {
                    $regions[$guid] = [
                        'name' => $name,
                        'count' => 0,
                    ];
                }
                $regions[$guid]['count']++;
            }
        }
        
        $this->comment("{$label}: найдено районов - " . count($regions));
        
        if (!empty($regions)) {
            $this->table(
                ['GUID', 'Название', 'Кол-во'],
                array_map(function($guid, $data) {
                    return [$guid, $data['name'], $data['count']];
                }, array_keys($regions), $regions)
            );
        }
    }
}
