<?php

namespace App\Services\TrendAgent;

/**
 * Нормализаторы для импорта — предотвращают Array to string conversion.
 */
class ImportNormalizers
{
    /**
     * Извлечь URL плана из array/object/string.
     */
    public static function planImageUrl(array $data, TrendAgentImageStoreService $imageStore): ?string
    {
        $raw = $data['plan_image'] ?? $data['plan_image_url'] ?? null;
        if ($raw === null) {
            return null;
        }
        if (is_string($raw)) {
            return $raw;
        }
        if (is_array($raw)) {
            if (isset($raw['url']) && is_string($raw['url'])) {
                return $raw['url'];
            }
            if (isset($raw['path'], $raw['file_name'])) {
                return $imageStore->buildUrlFromPathAndFile($raw['path'], $raw['file_name']);
            }
        }
        return null;
    }

    /**
     * Извлечь имя отделки из array/object/string.
     */
    public static function finishingName(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        if (is_string($raw)) {
            return $raw;
        }
        if (is_array($raw)) {
            return $raw['name'] ?? $raw['value'] ?? null;
        }
        return null;
    }

    /**
     * Извлечь имя статуса из array/object/string.
     */
    public static function statusName(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        if (is_string($raw)) {
            return $raw;
        }
        if (is_array($raw)) {
            return $raw['name'] ?? $raw['label'] ?? $raw['value'] ?? null;
        }
        return null;
    }
}
