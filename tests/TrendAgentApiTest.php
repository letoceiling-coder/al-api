<?php

/**
 * Тестовый скрипт для TrendAgent API
 * 
 * Запуск: php tests/TrendAgentApiTest.php
 */

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class TrendAgentApiTest
{
    private string $baseUrl = 'https://api.siteaccess.ru/api/trendagent';
    private string $token = '8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF';
    private string $phone = '+79045393434';
    private string $password = 'nwBvh4q';
    private string $cityId = '58c665588b6aa52311afa01b'; // Санкт-Петербург
    
    private Client $client;
    private array $results = [];
    private array $photoResults = [];
    
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 120,
            'verify' => false,
        ]);
    }
    
    public function runAllTests(): void
    {
        echo "=== TrendAgent API Testing ===\n\n";
        
        // 1. Тест авторизации
        $this->testAuthenticate();
        
        // 2. Тест получения городов
        $this->testGetCities();
        
        // 3. Тест квартир
        $this->testApartments();
        
        // 4. Тест паркингов
        $this->testParkings();
        
        // 5. Тест домов
        $this->testHouses();
        
        // 6. Тест участков
        $this->testPlots();
        
        // 7. Тест коммерческой недвижимости
        $this->testCommercial();
        
        // 8. Тест универсального списка объектов
        $this->testObjectsList();
        
        // 9. Генерация отчетов
        $this->generateReports();
    }
    
    private function testAuthenticate(): void
    {
        echo "1. Testing /authenticate...\n";
        
        try {
            $response = $this->client->post("{$this->baseUrl}/authenticate", [
                'headers' => [
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'phone' => $this->phone,
                    'password' => $this->password,
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data['success'] ?? false) {
                echo "   ✓ Authentication successful\n";
                $this->results['authenticate'] = ['status' => 'success', 'data' => $data];
            } else {
                echo "   ✗ Authentication failed\n";
                $this->results['authenticate'] = ['status' => 'failed', 'data' => $data];
            }
        } catch (GuzzleException $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
            $this->results['authenticate'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    private function testGetCities(): void
    {
        echo "2. Testing /cities...\n";
        
        try {
            $response = $this->client->get("{$this->baseUrl}/cities", [
                'headers' => [
                    'Authorization' => "Bearer {$this->token}",
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['data']) && is_array($data['data'])) {
                echo "   ✓ Cities retrieved: " . count($data['data']) . " cities\n";
                $this->results['cities'] = ['status' => 'success', 'count' => count($data['data']), 'data' => $data];
            } else {
                echo "   ✗ Failed to retrieve cities\n";
                $this->results['cities'] = ['status' => 'failed', 'data' => $data];
            }
        } catch (GuzzleException $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
            $this->results['cities'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    private function testApartments(): void
    {
        echo "3. Testing /apartments...\n";
        
        // Тест списка квартир
        try {
            $response = $this->client->post("{$this->baseUrl}/apartments", [
                'headers' => [
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'phone' => $this->phone,
                    'password' => $this->password,
                    'city' => $this->cityId,
                    'count' => 5,
                    'sort' => 'price',
                    'sort_order' => 'asc',
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data['success'] ?? false) {
                $objectsCount = count($data['data']['objects'] ?? []);
                echo "   ✓ Apartments list retrieved: {$objectsCount} objects\n";
                $this->results['apartments_list'] = ['status' => 'success', 'count' => $objectsCount, 'data' => $data];
                
                // Тест детальной информации для первого объекта
                if ($objectsCount > 0) {
                    $firstObject = $data['data']['objects'][0];
                    $objectId = $firstObject['_id'] ?? $firstObject['guid'] ?? null;
                    
                    if ($objectId) {
                        $this->testApartmentDetail($objectId, $firstObject);
                    }
                }
            } else {
                echo "   ✗ Failed to retrieve apartments list\n";
                $this->results['apartments_list'] = ['status' => 'failed', 'data' => $data];
            }
        } catch (GuzzleException $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
            $this->results['apartments_list'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    private function testApartmentDetail(string $objectId, array $objectData): void
    {
        echo "   Testing /apartments/{$objectId} (detail)...\n";
        
        try {
            $response = $this->client->post("{$this->baseUrl}/apartments/{$objectId}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'phone' => $this->phone,
                    'password' => $this->password,
                    'options' => [
                        'unified' => true,
                        'apartments' => true,
                        'plans' => true,
                        'progress' => true,
                        'finishings' => true,
                        'advantages' => true,
                        'nearby_places' => true,
                        'min_price' => true,
                        'videos' => true,
                        'files' => true,
                    ],
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data['success'] ?? false) {
                echo "   ✓ Apartment detail retrieved\n";
                $this->results['apartments_detail'] = ['status' => 'success', 'object_id' => $objectId, 'data' => $data];
                
                // Проверка фотографий
                $this->checkPhotos($data, 'apartments', $objectId);
            } else {
                echo "   ✗ Failed to retrieve apartment detail\n";
                $this->results['apartments_detail'] = ['status' => 'failed', 'data' => $data];
            }
        } catch (GuzzleException $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
            $this->results['apartments_detail'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
    
    private function testParkings(): void
    {
        echo "4. Testing /parkings...\n";
        
        try {
            $response = $this->client->post("{$this->baseUrl}/parkings", [
                'headers' => [
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'phone' => $this->phone,
                    'password' => $this->password,
                    'city' => $this->cityId,
                    'count' => 5,
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data['success'] ?? false) {
                $objectsCount = count($data['data']['objects'] ?? []);
                echo "   ✓ Parkings list retrieved: {$objectsCount} objects\n";
                $this->results['parkings_list'] = ['status' => 'success', 'count' => $objectsCount, 'data' => $data];
            } else {
                echo "   ✗ Failed to retrieve parkings list\n";
                $this->results['parkings_list'] = ['status' => 'failed', 'data' => $data];
            }
        } catch (GuzzleException $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
            $this->results['parkings_list'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    private function testHouses(): void
    {
        echo "5. Testing /houses...\n";
        
        try {
            $response = $this->client->post("{$this->baseUrl}/houses", [
                'headers' => [
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'phone' => $this->phone,
                    'password' => $this->password,
                    'city' => $this->cityId,
                    'count' => 5,
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data['success'] ?? false) {
                $objectsCount = count($data['data']['objects'] ?? []);
                echo "   ✓ Houses list retrieved: {$objectsCount} objects\n";
                $this->results['houses_list'] = ['status' => 'success', 'count' => $objectsCount, 'data' => $data];
            } else {
                echo "   ✗ Failed to retrieve houses list\n";
                $this->results['houses_list'] = ['status' => 'failed', 'data' => $data];
            }
        } catch (GuzzleException $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
            $this->results['houses_list'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    private function testPlots(): void
    {
        echo "6. Testing /plots...\n";
        
        try {
            $response = $this->client->post("{$this->baseUrl}/plots", [
                'headers' => [
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'phone' => $this->phone,
                    'password' => $this->password,
                    'city' => $this->cityId,
                    'count' => 5,
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data['success'] ?? false) {
                $objectsCount = count($data['data']['objects'] ?? []);
                echo "   ✓ Plots list retrieved: {$objectsCount} objects\n";
                $this->results['plots_list'] = ['status' => 'success', 'count' => $objectsCount, 'data' => $data];
            } else {
                echo "   ✗ Failed to retrieve plots list\n";
                $this->results['plots_list'] = ['status' => 'failed', 'data' => $data];
            }
        } catch (GuzzleException $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
            $this->results['plots_list'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    private function testCommercial(): void
    {
        echo "7. Testing /commercial...\n";
        
        try {
            $response = $this->client->post("{$this->baseUrl}/commercial", [
                'headers' => [
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'phone' => $this->phone,
                    'password' => $this->password,
                    'city' => $this->cityId,
                    'count' => 5,
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data['success'] ?? false) {
                $objectsCount = count($data['data']['objects'] ?? []);
                echo "   ✓ Commercial list retrieved: {$objectsCount} objects\n";
                $this->results['commercial_list'] = ['status' => 'success', 'count' => $objectsCount, 'data' => $data];
            } else {
                echo "   ✗ Failed to retrieve commercial list\n";
                $this->results['commercial_list'] = ['status' => 'failed', 'data' => $data];
            }
        } catch (GuzzleException $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
            $this->results['commercial_list'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    private function testObjectsList(): void
    {
        echo "8. Testing /objects/list...\n";
        
        $objectTypes = ['apartments', 'parking', 'houses', 'plots', 'commercial'];
        
        foreach ($objectTypes as $type) {
            echo "   Testing object_type: {$type}...\n";
            
            try {
                $response = $this->client->post("{$this->baseUrl}/objects/list", [
                    'headers' => [
                        'Authorization' => "Bearer {$this->token}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'phone' => $this->phone,
                        'password' => $this->password,
                        'object_type' => $type,
                        'city' => $this->cityId,
                        'count' => 3,
                    ],
                ]);
                
                $data = json_decode($response->getBody()->getContents(), true);
                
                if ($data['success'] ?? false) {
                    $objectsCount = count($data['data']['objects'] ?? []);
                    echo "     ✓ {$type}: {$objectsCount} objects\n";
                    $this->results["objects_list_{$type}"] = ['status' => 'success', 'count' => $objectsCount, 'data' => $data];
                } else {
                    echo "     ✗ {$type}: failed\n";
                    $this->results["objects_list_{$type}"] = ['status' => 'failed', 'data' => $data];
                }
            } catch (GuzzleException $e) {
                echo "     ✗ {$type}: error - " . $e->getMessage() . "\n";
                $this->results["objects_list_{$type}"] = ['status' => 'error', 'error' => $e->getMessage()];
            }
        }
        
        echo "\n";
    }
    
    private function checkPhotos(array $data, string $type, string $objectId): void
    {
        $photos = [];
        
        // Проверяем unified данные
        if (isset($data['data']['unified']['data']['renderer'])) {
            foreach ($data['data']['unified']['data']['renderer'] as $image) {
                $url = $image['url'] ?? null;
                if ($url) {
                    $photos[] = [
                        'type' => 'unified_renderer',
                        'url' => $url,
                        'object_id' => $objectId,
                        'object_type' => $type,
                    ];
                }
            }
        }
        
        // Проверяем изображения в apartments
        if (isset($data['data']['apartments']['data'])) {
            foreach ($data['data']['apartments']['data'] as $apartment) {
                if (isset($apartment['plan'])) {
                    $planUrl = $this->buildImageUrl($apartment['plan']);
                    if ($planUrl) {
                        $photos[] = [
                            'type' => 'apartment_plan',
                            'url' => $planUrl,
                            'object_id' => $objectId,
                            'object_type' => $type,
                            'apartment_id' => $apartment['_id'] ?? null,
                        ];
                    }
                }
            }
        }
        
        // Проверяем plans
        if (isset($data['data']['plans']['data'])) {
            foreach ($data['data']['plans']['data'] as $plan) {
                $planUrl = $this->buildImageUrl($plan);
                if ($planUrl) {
                    $photos[] = [
                        'type' => 'plan',
                        'url' => $planUrl,
                        'object_id' => $objectId,
                        'object_type' => $type,
                    ];
                }
            }
        }
        
        // Проверяем изображения в списке объектов
        if (isset($data['data']['objects'])) {
            foreach ($data['data']['objects'] as $object) {
                if (isset($object['image'])) {
                    $imageUrl = $object['image']['url'] ?? $object['image']['url_full'] ?? null;
                    if ($imageUrl) {
                        $photos[] = [
                            'type' => 'object_image',
                            'url' => $imageUrl,
                            'object_id' => $object['_id'] ?? $object['guid'] ?? null,
                            'object_type' => $type,
                        ];
                    }
                }
            }
        }
        
        // Проверяем доступность каждой фотографии
        foreach ($photos as $photo) {
            $this->checkPhotoAvailability($photo);
        }
    }
    
    private function buildImageUrl(array $plan): ?string
    {
        if (isset($plan['file_name']) && isset($plan['path'])) {
            $fileName = $plan['file_name'];
            $path = $plan['path'];
            return "https://selcdn.trendagent.ru/images/{$path}m_{$fileName}";
        }
        return null;
    }
    
    private function checkPhotoAvailability(array $photo): void
    {
        try {
            $response = $this->client->head($photo['url'], [
                'timeout' => 10,
                'allow_redirects' => true,
            ]);
            
            $statusCode = $response->getStatusCode();
            $contentType = $response->getHeader('Content-Type')[0] ?? 'unknown';
            $contentLength = $response->getHeader('Content-Length')[0] ?? 'unknown';
            
            $this->photoResults[] = [
                'url' => $photo['url'],
                'type' => $photo['type'],
                'object_id' => $photo['object_id'],
                'object_type' => $photo['object_type'],
                'status' => 'available',
                'status_code' => $statusCode,
                'content_type' => $contentType,
                'content_length' => $contentLength,
            ];
        } catch (GuzzleException $e) {
            $this->photoResults[] = [
                'url' => $photo['url'],
                'type' => $photo['type'],
                'object_id' => $photo['object_id'],
                'object_type' => $photo['object_type'],
                'status' => 'unavailable',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    private function generateReports(): void
    {
        echo "=== Generating Reports ===\n\n";
        
        // Общий отчет
        $this->generateGeneralReport();
        
        // Отчет по фотографиям
        $this->generatePhotoReport();
        
        echo "Reports generated successfully!\n";
    }
    
    private function generateGeneralReport(): void
    {
        $report = "# TrendAgent API Test Report\n\n";
        $report .= "**Date:** " . date('Y-m-d H:i:s') . "\n\n";
        $report .= "## Test Summary\n\n";
        
        $total = count($this->results);
        $success = 0;
        $failed = 0;
        $errors = 0;
        
        foreach ($this->results as $test => $result) {
            if ($result['status'] === 'success') {
                $success++;
            } elseif ($result['status'] === 'failed') {
                $failed++;
            } else {
                $errors++;
            }
        }
        
        $report .= "- **Total Tests:** {$total}\n";
        $report .= "- **Successful:** {$success}\n";
        $report .= "- **Failed:** {$failed}\n";
        $report .= "- **Errors:** {$errors}\n\n";
        
        $report .= "## Detailed Results\n\n";
        
        foreach ($this->results as $test => $result) {
            $status = $result['status'];
            $icon = $status === 'success' ? '✓' : ($status === 'failed' ? '✗' : '⚠');
            
            $report .= "### {$icon} {$test}\n\n";
            $report .= "**Status:** {$status}\n\n";
            
            if (isset($result['count'])) {
                $report .= "**Count:** {$result['count']}\n\n";
            }
            
            if (isset($result['error'])) {
                $report .= "**Error:** {$result['error']}\n\n";
            }
            
            if (isset($result['data']['message'])) {
                $report .= "**Message:** {$result['data']['message']}\n\n";
            }
            
            $report .= "---\n\n";
        }
        
        file_put_contents(__DIR__ . '/../TRENDAGENT_API_TEST_REPORT.md', $report);
        echo "General report saved to TRENDAGENT_API_TEST_REPORT.md\n";
    }
    
    private function generatePhotoReport(): void
    {
        $report = "# TrendAgent API Photo Availability Report\n\n";
        $report .= "**Date:** " . date('Y-m-d H:i:s') . "\n\n";
        $report .= "## Summary\n\n";
        
        $total = count($this->photoResults);
        $available = 0;
        $unavailable = 0;
        
        foreach ($this->photoResults as $photo) {
            if ($photo['status'] === 'available') {
                $available++;
            } else {
                $unavailable++;
            }
        }
        
        $report .= "- **Total Photos Checked:** {$total}\n";
        $report .= "- **Available:** {$available} (" . round($available / max($total, 1) * 100, 2) . "%)\n";
        $report .= "- **Unavailable:** {$unavailable} (" . round($unavailable / max($total, 1) * 100, 2) . "%)\n\n";
        
        $report .= "## Photo Details\n\n";
        
        foreach ($this->photoResults as $photo) {
            $status = $photo['status'];
            $icon = $status === 'available' ? '✓' : '✗';
            
            $report .= "### {$icon} {$photo['type']}\n\n";
            $report .= "- **URL:** {$photo['url']}\n";
            $report .= "- **Object ID:** {$photo['object_id']}\n";
            $report .= "- **Object Type:** {$photo['object_type']}\n";
            $report .= "- **Status:** {$status}\n";
            
            if ($status === 'available') {
                $report .= "- **Status Code:** {$photo['status_code']}\n";
                $report .= "- **Content Type:** {$photo['content_type']}\n";
                $report .= "- **Content Length:** {$photo['content_length']}\n";
            } else {
                $report .= "- **Error:** {$photo['error']}\n";
            }
            
            $report .= "\n---\n\n";
        }
        
        // Группировка по типам
        $report .= "## Statistics by Type\n\n";
        
        $byType = [];
        foreach ($this->photoResults as $photo) {
            $type = $photo['type'];
            if (!isset($byType[$type])) {
                $byType[$type] = ['total' => 0, 'available' => 0, 'unavailable' => 0];
            }
            $byType[$type]['total']++;
            if ($photo['status'] === 'available') {
                $byType[$type]['available']++;
            } else {
                $byType[$type]['unavailable']++;
            }
        }
        
        foreach ($byType as $type => $stats) {
            $report .= "### {$type}\n\n";
            $report .= "- **Total:** {$stats['total']}\n";
            $report .= "- **Available:** {$stats['available']} (" . round($stats['available'] / max($stats['total'], 1) * 100, 2) . "%)\n";
            $report .= "- **Unavailable:** {$stats['unavailable']} (" . round($stats['unavailable'] / max($stats['total'], 1) * 100, 2) . "%)\n\n";
        }
        
        file_put_contents(__DIR__ . '/../TRENDAGENT_API_PHOTO_REPORT.md', $report);
        echo "Photo report saved to TRENDAGENT_API_PHOTO_REPORT.md\n";
    }
}

// Запуск тестов
$tester = new TrendAgentApiTest();
$tester->runAllTests();
