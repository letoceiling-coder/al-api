<?php

namespace App\Services\TrendAgent;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class ImageDownloader
{
    protected string $baseStoragePath;
    protected bool $downloadEnabled = false;
    protected array $stats = [];

    public function __construct(bool $downloadEnabled = false)
    {
        $this->baseStoragePath = storage_path('app/public/trendagent');
        $this->downloadEnabled = $downloadEnabled;
        $this->stats = [
            'total_urls' => 0,
            'downloaded' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
    }

    /**
     * Обработка изображения: сохранить только URL или скачать файл
     * 
     * @param string $imageUrl URL изображения
     * @param string $objectType Тип объекта (complex, apartment, parking, etc.)
     * @param string $objectId ID объекта
     * @param string $imageType Тип изображения (gallery, plan, view, genplan)
     * @param int|null $order Порядковый номер (для галереи)
     * @return array Данные изображения
     */
    public function processImage(
        string $imageUrl,
        string $objectType,
        string $objectId,
        string $imageType = 'gallery',
        ?int $order = null
    ): array {
        $this->stats['total_urls']++;

        $imageData = [
            'url' => $imageUrl,
            'local_path' => null,
            'thumbnail_path' => null,
            'file_size' => null,
            'width' => null,
            'height' => null,
            'mime_type' => null,
            'type' => $imageType,
            'order' => $order,
            'downloaded_at' => null,
        ];

        // Если скачивание отключено - возвращаем только URL
        if (!$this->downloadEnabled) {
            $this->stats['skipped']++;
            return $imageData;
        }

        // Скачиваем изображение
        try {
            $downloadResult = $this->downloadImage($imageUrl, $objectType, $objectId, $imageType, $order);
            
            if ($downloadResult['success']) {
                $imageData['local_path'] = $downloadResult['local_path'];
                $imageData['thumbnail_path'] = $downloadResult['thumbnail_path'] ?? null;
                $imageData['file_size'] = $downloadResult['file_size'];
                $imageData['width'] = $downloadResult['width'] ?? null;
                $imageData['height'] = $downloadResult['height'] ?? null;
                $imageData['mime_type'] = $downloadResult['mime_type'];
                $imageData['downloaded_at'] = now()->toIso8601String();
                $this->stats['downloaded']++;
            } else {
                $this->stats['errors']++;
                Log::warning("Failed to download image: {$imageUrl}", [
                    'error' => $downloadResult['error'] ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            $this->stats['errors']++;
            Log::error("Exception while downloading image: {$imageUrl}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $imageData;
    }

    /**
     * Скачать изображение с внешнего URL
     */
    protected function downloadImage(
        string $imageUrl,
        string $objectType,
        string $objectId,
        string $imageType,
        ?int $order
    ): array {
        try {
            // Генерируем хеш для имени файла
            $urlHash = md5($imageUrl);
            $extension = $this->getExtensionFromUrl($imageUrl);
            
            // Если у нас есть order, добавляем его к имени файла
            $filename = $order !== null 
                ? "{$urlHash}_{$order}.{$extension}"
                : "{$urlHash}.{$extension}";

            // Формируем путь для сохранения
            $relativePath = "images/{$objectType}/{$objectId}/{$imageType}";
            $fullPath = "{$this->baseStoragePath}/{$relativePath}";
            $filePath = "{$fullPath}/{$filename}";

            // Если файл уже существует - пропускаем
            if (File::exists($filePath)) {
                $fileSize = File::size($filePath);
                list($width, $height) = @getimagesize($filePath) ?: [null, null];
                
                return [
                    'success' => true,
                    'local_path' => "/storage/trendagent/{$relativePath}/{$filename}",
                    'file_size' => $fileSize,
                    'width' => $width,
                    'height' => $height,
                    'mime_type' => mime_content_type($filePath),
                ];
            }

            // Создаём директорию если не существует
            File::ensureDirectoryExists($fullPath);

            // Скачиваем файл
            $response = Http::timeout(30)->get($imageUrl);
            
            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}"
                ];
            }

            // Сохраняем файл
            File::put($filePath, $response->body());
            
            // Получаем метаданные
            $fileSize = File::size($filePath);
            list($width, $height) = @getimagesize($filePath) ?: [null, null];
            $mimeType = mime_content_type($filePath);

            // Создаём миниатюру для галерейных изображений
            $thumbnailPath = null;
            if ($imageType === 'gallery' || $imageType === 'view') {
                $thumbnailPath = $this->createThumbnail($filePath, $objectType, $objectId, $imageType, $filename);
            }

            return [
                'success' => true,
                'local_path' => "/storage/trendagent/{$relativePath}/{$filename}",
                'thumbnail_path' => $thumbnailPath,
                'file_size' => $fileSize,
                'width' => $width,
                'height' => $height,
                'mime_type' => $mimeType,
            ];

        } catch (RequestException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Создать миниатюру изображения
     */
    protected function createThumbnail(
        string $originalPath,
        string $objectType,
        string $objectId,
        string $imageType,
        string $filename
    ): ?string {
        try {
            // Путь для миниатюры
            $relativePath = "thumbnails/{$objectType}/{$objectId}/{$imageType}";
            $fullPath = "{$this->baseStoragePath}/{$relativePath}";
            $thumbnailFilename = pathinfo($filename, PATHINFO_FILENAME) . '_thumb.' . pathinfo($filename, PATHINFO_EXTENSION);
            $thumbnailPath = "{$fullPath}/{$thumbnailFilename}";

            // Создаём директорию
            File::ensureDirectoryExists($fullPath);

            // Создаём миниатюру (используем GD или ImageMagick если доступен)
            if (extension_loaded('gd')) {
                $this->createThumbnailGD($originalPath, $thumbnailPath, 300, 300);
                return "/storage/trendagent/{$relativePath}/{$thumbnailFilename}";
            }

            Log::warning('GD extension not available, thumbnail not created');
            return null;

        } catch (\Exception $e) {
            Log::error('Failed to create thumbnail', [
                'error' => $e->getMessage(),
                'file' => $originalPath,
            ]);
            return null;
        }
    }

    /**
     * Создать миниатюру с помощью GD
     */
    protected function createThumbnailGD(string $source, string $destination, int $maxWidth, int $maxHeight): void
    {
        list($srcWidth, $srcHeight, $type) = getimagesize($source);

        // Вычисляем новые размеры с сохранением пропорций
        $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
        $newWidth = (int) ($srcWidth * $ratio);
        $newHeight = (int) ($srcHeight * $ratio);

        // Создаём исходное изображение
        switch ($type) {
            case IMAGETYPE_JPEG:
                $srcImage = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $srcImage = imagecreatefrompng($source);
                break;
            case IMAGETYPE_GIF:
                $srcImage = imagecreatefromgif($source);
                break;
            default:
                throw new \Exception("Unsupported image type: {$type}");
        }

        // Создаём миниатюру
        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Для PNG сохраняем прозрачность
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
            imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Ресайзим
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

        // Сохраняем
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($dstImage, $destination, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($dstImage, $destination, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($dstImage, $destination);
                break;
        }

        // Освобождаем память
        imagedestroy($srcImage);
        imagedestroy($dstImage);
    }

    /**
     * Получить расширение файла из URL
     */
    protected function getExtensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        // Если расширения нет или оно странное - используем jpg по умолчанию
        if (empty($extension) || !in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return 'jpg';
        }
        
        return strtolower($extension);
    }

    /**
     * Извлечь URL изображений из данных объекта (complex, apartment и т.д.)
     *
     * @param array $data Данные объекта из API
     * @return array ['gallery' => [], 'plans' => [], 'views' => []]
     */
    public function extractImageUrls(array $data): array
    {
        $result = ['gallery' => [], 'plans' => [], 'views' => []];
        $data = $data['data'] ?? $data;

        $extractUrl = function ($item): ?string {
            if (is_string($item)) {
                return $item;
            }
            if (is_array($item)) {
                if (!empty($item['url'])) {
                    return $item['url'];
                }
                if (!empty($item['path']) && !empty($item['file_name'])) {
                    return 'https://selcdn.trendagent.ru/images/' . $item['path'] . '/' . $item['file_name'];
                }
            }
            return null;
        };

        foreach (['renderer', 'images', 'gallery_images', 'plan'] as $key) {
            if (empty($data[$key])) {
                continue;
            }
            $items = is_array($data[$key]) ? $data[$key] : [$data[$key]];
            foreach ($items as $item) {
                $url = $extractUrl($item);
                if ($url) {
                    $result['gallery'][] = $url;
                }
            }
        }
        if (!empty($data['plan']['url'] ?? $data['plan_image']['url'] ?? null)) {
            $result['plans'][] = $data['plan']['url'] ?? $data['plan_image']['url'];
        }
        return $result;
    }

    /**
     * Обработать массив изображений
     * 
     * @param array $imageUrls Массив URL изображений
     * @param string $objectType Тип объекта
     * @param string $objectId ID объекта
     * @param string $imageType Тип изображения
     * @return array Массив обработанных изображений
     */
    public function processImages(
        array $imageUrls,
        string $objectType,
        string $objectId,
        string $imageType = 'gallery'
    ): array {
        $processedImages = [];
        
        foreach ($imageUrls as $index => $imageUrl) {
            if (empty($imageUrl)) {
                continue;
            }
            
            $processedImages[] = $this->processImage(
                $imageUrl,
                $objectType,
                $objectId,
                $imageType,
                $index + 1
            );
        }
        
        return $processedImages;
    }

    /**
     * Получить статистику обработки
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Сбросить статистику
     */
    public function resetStats(): void
    {
        $this->stats = [
            'total_urls' => 0,
            'downloaded' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
    }

    /**
     * Включить/выключить скачивание
     */
    public function setDownloadEnabled(bool $enabled): void
    {
        $this->downloadEnabled = $enabled;
    }

    /**
     * Проверить, включено ли скачивание
     */
    public function isDownloadEnabled(): bool
    {
        return $this->downloadEnabled;
    }
}
