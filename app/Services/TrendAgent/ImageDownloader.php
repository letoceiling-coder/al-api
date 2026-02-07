<?php

namespace App\Services\TrendAgent;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImageDownloader
{
    protected $imageManager;
    protected $basePath = 'trendagent/images';
    protected $thumbnailPath = 'trendagent/thumbnails';
    
    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }
    
    /**
     * Скачать изображение с URL и сохранить локально
     * 
     * @param string $url URL изображения
     * @param string $type Тип объекта (complex, apartment, parking, house, plot, commercial)
     * @param string $objectId ID объекта
     * @param string $category Категория (gallery, plans, views)
     * @param bool $createThumbnail Создавать ли миниатюру
     * @return array|null Массив с информацией об изображении или null при ошибке
     */
    public function download(string $url, string $type, string $objectId, string $category = 'gallery', bool $createThumbnail = true): ?array
    {
        try {
            // Валидация URL
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                Log::warning('Invalid image URL', ['url' => $url]);
                return null;
            }
            
            // Генерируем имя файла на основе хеша URL
            $urlHash = md5($url);
            $extension = $this->getExtensionFromUrl($url);
            $filename = $urlHash . '.' . $extension;
            
            // Путь для сохранения
            $relativePath = "{$this->basePath}/{$type}/{$objectId}/{$category}/{$filename}";
            $fullPath = storage_path("app/public/{$relativePath}");
            
            // Проверяем, существует ли уже файл
            if (Storage::disk('public')->exists($relativePath)) {
                Log::info('Image already exists', ['path' => $relativePath]);
                return $this->getImageInfo($relativePath, $url, $type, $objectId, $category);
            }
            
            // Создаем директорию если не существует
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Скачиваем изображение
            $imageContent = $this->fetchImage($url);
            if ($imageContent === null) {
                return null;
            }
            
            // Сохраняем оригинал
            file_put_contents($fullPath, $imageContent);
            
            // Получаем информацию об изображении
            $imageInfo = $this->getImageInfo($relativePath, $url, $type, $objectId, $category);
            
            // Создаем миниатюру если нужно
            if ($createThumbnail) {
                $this->createThumbnail($fullPath, $type, $objectId, $category, $urlHash, $extension);
            }
            
            Log::info('Image downloaded successfully', [
                'url' => $url,
                'path' => $relativePath,
                'size' => $imageInfo['file_size']
            ]);
            
            return $imageInfo;
            
        } catch (\Exception $e) {
            Log::error('Error downloading image', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Скачать несколько изображений
     * 
     * @param array $urls Массив URL изображений
     * @param string $type Тип объекта
     * @param string $objectId ID объекта
     * @param string $category Категория
     * @return array Массив информации об изображениях
     */
    public function downloadMultiple(array $urls, string $type, string $objectId, string $category = 'gallery'): array
    {
        $results = [];
        
        foreach ($urls as $index => $url) {
            if (empty($url)) {
                continue;
            }
            
            $result = $this->download($url, $type, $objectId, $category);
            if ($result) {
                $result['order'] = $index;
                $results[] = $result;
            }
            
            // Небольшая задержка между запросами
            usleep(100000); // 0.1 секунды
        }
        
        return $results;
    }
    
    /**
     * Извлечь все URL изображений из данных объекта
     * 
     * @param array $data Данные объекта
     * @return array Массив URL изображений по категориям
     */
    public function extractImageUrls(array $data): array
    {
        $images = [
            'gallery' => [],
            'plans' => [],
            'views' => []
        ];
        
        // Галерея
        if (isset($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $image) {
                $url = $this->extractUrl($image);
                if ($url) {
                    $images['gallery'][] = $url;
                }
            }
        }
        
        if (isset($data['gallery']) && is_array($data['gallery'])) {
            foreach ($data['gallery'] as $image) {
                $url = $this->extractUrl($image);
                if ($url) {
                    $images['gallery'][] = $url;
                }
            }
        }
        
        // Планы
        if (isset($data['plan']) && !empty($data['plan'])) {
            $url = $this->extractUrl($data['plan']);
            if ($url) {
                $images['plans'][] = $url;
            }
        }
        
        if (isset($data['plan_image']) && !empty($data['plan_image'])) {
            $url = $this->extractUrl($data['plan_image']);
            if ($url) {
                $images['plans'][] = $url;
            }
        }
        
        if (isset($data['plans']) && is_array($data['plans'])) {
            foreach ($data['plans'] as $plan) {
                $url = $this->extractUrl($plan);
                if ($url) {
                    $images['plans'][] = $url;
                }
            }
        }
        
        // Виды
        if (isset($data['view_image']) && !empty($data['view_image'])) {
            $url = $this->extractUrl($data['view_image']);
            if ($url) {
                $images['views'][] = $url;
            }
        }
        
        if (isset($data['view']) && !empty($data['view'])) {
            $url = $this->extractUrl($data['view']);
            if ($url) {
                $images['views'][] = $url;
            }
        }
        
        // Убираем дубликаты
        foreach ($images as $category => $urls) {
            $images[$category] = array_unique($urls);
        }
        
        return $images;
    }
    
    /**
     * Обновить URL в данных объекта на локальные пути
     * 
     * @param array $data Данные объекта
     * @param array $downloadedImages Массив скачанных изображений
     * @return array Обновленные данные
     */
    public function updateImageUrls(array $data, array $downloadedImages): array
    {
        // Создаем маппинг оригинальных URL на локальные
        $urlMapping = [];
        foreach ($downloadedImages as $img) {
            $urlMapping[$img['original_url']] = $img['public_url'];
        }
        
        // Рекурсивно обновляем URL в данных
        return $this->replaceUrlsRecursive($data, $urlMapping);
    }
    
    /**
     * Создать миниатюру изображения
     */
    protected function createThumbnail(string $originalPath, string $type, string $objectId, string $category, string $hash, string $extension): void
    {
        try {
            $thumbnailPath = "{$this->thumbnailPath}/{$type}/{$objectId}/{$category}/{$hash}_thumb.{$extension}";
            $fullThumbnailPath = storage_path("app/public/{$thumbnailPath}");
            
            // Проверяем существование
            if (Storage::disk('public')->exists($thumbnailPath)) {
                return;
            }
            
            // Создаем директорию
            $directory = dirname($fullThumbnailPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Создаем миниатюру
            $image = $this->imageManager->read($originalPath);
            $image->scale(width: 800, height: 800);
            $image->save($fullThumbnailPath, quality: 85);
            
        } catch (\Exception $e) {
            Log::warning('Error creating thumbnail', [
                'path' => $originalPath,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Получить информацию об изображении
     */
    protected function getImageInfo(string $relativePath, string $originalUrl, string $type, string $objectId, string $category): array
    {
        $fullPath = storage_path("app/public/{$relativePath}");
        $publicUrl = Storage::disk('public')->url($relativePath);
        
        $info = [
            'original_url' => $originalUrl,
            'local_path' => $relativePath,
            'public_url' => $publicUrl,
            'type' => $type,
            'object_id' => $objectId,
            'category' => $category,
            'file_size' => filesize($fullPath),
            'downloaded_at' => now()->toIso8601String()
        ];
        
        // Получаем размеры изображения
        try {
            $image = $this->imageManager->read($fullPath);
            $info['width'] = $image->width();
            $info['height'] = $image->height();
            $info['mime_type'] = mime_content_type($fullPath);
        } catch (\Exception $e) {
            Log::warning('Error getting image dimensions', ['path' => $fullPath]);
        }
        
        return $info;
    }
    
    /**
     * Извлечь URL из различных форматов данных изображения
     */
    protected function extractUrl($image): ?string
    {
        if (is_string($image)) {
            return $this->normalizeUrl($image);
        }
        
        if (is_array($image)) {
            // Различные варианты ключей
            $urlKeys = ['url', 'src', 'image', 'image_url', 'original_url', 'path'];
            foreach ($urlKeys as $key) {
                if (isset($image[$key]) && !empty($image[$key])) {
                    return $this->normalizeUrl($image[$key]);
                }
            }
        }
        
        return null;
    }
    
    /**
     * Нормализовать URL (добавить протокол если нужно)
     */
    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);
        
        // Если URL начинается с //, добавляем https:
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }
        
        // Если URL относительный, добавляем базовый домен
        if (strpos($url, 'http') !== 0) {
            // Предполагаем, что это URL от selcdn.trendagent.ru
            if (strpos($url, '/') === 0) {
                $url = 'https://selcdn.trendagent.ru' . $url;
            }
        }
        
        return $url;
    }
    
    /**
     * Получить расширение файла из URL
     */
    protected function getExtensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        // Если расширение не найдено, пытаемся определить по Content-Type
        if (empty($extension)) {
            return 'jpg'; // По умолчанию
        }
        
        return strtolower($extension);
    }
    
    /**
     * Скачать изображение по URL
     */
    protected function fetchImage(string $url): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withOptions([
                    'verify' => false,
                    'allow_redirects' => true,
                    'max_redirects' => 5,
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);
            
            if (!$response->successful()) {
                Log::warning('HTTP error downloading image', ['url' => $url, 'code' => $response->status()]);
                return null;
            }
            
            return $response->body();
            
        } catch (\Exception $e) {
            Log::error('Exception downloading image', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Рекурсивно заменить URL в массиве данных
     */
    protected function replaceUrlsRecursive($data, array $urlMapping)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->replaceUrlsRecursive($value, $urlMapping);
            }
        } elseif (is_string($data)) {
            // Проверяем, является ли строка URL из маппинга
            if (isset($urlMapping[$data])) {
                return $urlMapping[$data];
            }
        }
        
        return $data;
    }
}
