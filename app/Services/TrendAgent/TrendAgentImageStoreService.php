<?php

namespace App\Services\TrendAgent;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TrendAgentImageStoreService
{
    private string $disk = 'public';
    private string $baseDir = 'trendagent';
    private string $baseUrl = 'https://selcdn.trendagent.ru/images/';
    private int $timeoutSeconds = 30;
    private int $maxSizeBytes;
    private int $retries = 2;

    /**
     * @param string|null $disk
     * @param string|null $baseDir
     * @param int|null $timeoutSeconds
     * @param int|null $maxSizeMb
     * @param int|null $retries
     */
    public function __construct(
        ?string $disk = null,
        ?string $baseDir = null,
        ?int $timeoutSeconds = null,
        ?int $maxSizeMb = null,
        ?int $retries = null
    ) {
        if ($disk !== null) {
            $this->disk = $disk;
        }
        if ($baseDir !== null) {
            $this->baseDir = $baseDir;
        }
        $this->timeoutSeconds = $timeoutSeconds ?? 30;
        $this->maxSizeBytes = ($maxSizeMb ?? 25) * 1024 * 1024;
        $this->retries = $retries ?? 2;
    }

    /**
     * Скачать изображение по URL и сохранить в storage.
     * Возвращает ['local_path' => ..., 'mime' => ..., 'size' => ..., 'hash' => ...]
     */
    public function storeFromUrl(
        string $url,
        string $entityType,
        int|string $entityId,
        ?int $orderIndex = null
    ): array {
        $result = [
            'local_path' => null,
            'mime' => null,
            'size' => null,
            'hash' => null,
        ];

        $url = $this->normalizeUrl($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $result;
        }

        try {
            $lastError = null;
            for ($attempt = 0; $attempt <= $this->retries; $attempt++) {
                $response = Http::timeout($this->timeoutSeconds)
                    ->withOptions(['verify' => true])
                    ->get($url);

                if ($response->successful()) {
                    break;
                }
                $lastError = $response->status();
                if ($attempt < $this->retries) {
                    usleep(200000); // 200ms между попытками
                } else {
                    return $result;
                }
            }

            $content = $response->body();
            $size = strlen($content);

            if ($size > $this->maxSizeBytes) {
                return $result;
            }

            $hash = hash('sha1', $content);
            $mime = $this->detectMime($content, $url);
            $ext = $this->extFromMimeOrUrl($mime, $url);

            $relativePath = $this->buildPath($entityType, $entityId, $hash, $ext);
            $fullPath = $this->baseDir . '/' . $relativePath;

            $storage = Storage::disk($this->disk);
            if ($storage->exists($fullPath)) {
                $result['local_path'] = $fullPath;
                $result['mime'] = $mime;
                $result['size'] = $size;
                $result['hash'] = $hash;
                return $result;
            }

            $dir = dirname($fullPath);
            if (!$storage->exists($dir)) {
                $storage->makeDirectory($dir);
            }

            if ($storage->put($fullPath, $content)) {
                $result['local_path'] = $fullPath;
                $result['mime'] = $mime;
                $result['size'] = $size;
                $result['hash'] = $hash;
            }
        } catch (\Throwable $e) {
            // Ошибка — возвращаем пустой результат
        }

        return $result;
    }

    /**
     * Построить путь: {entityType}/{entityId}/{hash}.{ext}
     */
    public function buildPath(string $entityType, int|string $entityId, string $hash, string $ext): string
    {
        $safeType = Str::slug($entityType);
        $safeId = is_string($entityId) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $entityId) : (string) $entityId;
        return "{$safeType}/{$safeId}/{$hash}.{$ext}";
    }

    /**
     * Нормализовать URL (path + file_name → полный URL)
     */
    public function normalizeUrl(string $urlOrPath): string
    {
        if (str_starts_with($urlOrPath, 'http://') || str_starts_with($urlOrPath, 'https://')) {
            return $urlOrPath;
        }
        $path = ltrim(str_replace('\\', '/', $urlOrPath), '/');
        return rtrim($this->baseUrl, '/') . '/' . $path;
    }

    /**
     * Собрать URL из path + file_name (TrendAgent формат)
     */
    public function buildUrlFromPathAndFile(?string $path, ?string $fileName): ?string
    {
        if (!$path || !$fileName) {
            return null;
        }
        $combined = rtrim($path, '/') . '/' . ltrim($fileName, '/');
        return $this->normalizeUrl($combined);
    }

    private function detectMime(string $content, string $url): ?string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($content);
        if ($detected && $detected !== 'application/octet-stream') {
            return $detected;
        }
        return $this->mimeFromUrl($url);
    }

    private function mimeFromUrl(string $url): ?string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => null,
        };
    }

    private function extFromMimeOrUrl(?string $mime, string $url): string
    {
        if ($mime) {
            $map = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg',
            ];
            if (isset($map[$mime])) {
                return $map[$mime];
            }
        }
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']) ? $ext : 'jpg';
    }
}
