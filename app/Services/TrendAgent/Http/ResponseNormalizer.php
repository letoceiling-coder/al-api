<?php

namespace App\Services\TrendAgent\Http;

use Illuminate\Http\Client\Response;

/**
 * Нормализатор ответов API
 * 
 * Ответственность:
 * - Нормализация разных форматов ответов в единый
 * - Маппинг полей по схеме
 * - Обработка разных naming conventions
 * 
 * ГРАНИЦА:
 * Raw API response → Normalized array
 */
class ResponseNormalizer
{
    /**
     * Нормализовать каталожный ответ
     * 
     * Обрабатывает разные форматы:
     * - { data: { results: [...], total: N } }
     * - { data: { list: [...], apartmentsCount: N } }
     * - { items: [...], count: N }
     * - [...] (массив без обёртки)
     */
    public function normalizeCatalogResponse(Response $response): array
    {
        if (!$response->successful()) {
            return ['items' => [], 'total' => 0];
        }

        $data = $response->json();
        if (!is_array($data)) {
            return ['items' => [], 'total' => 0];
        }

        // Если это массив — вернуть как есть
        if (isset($data[0]) && !isset($data['data'])) {
            return [
                'items' => $data,
                'total' => count($data),
            ];
        }

        // Обработка вложенной структуры data
        $dataSection = $data['data'] ?? $data;
        
        // Извлечение items/results/list
        $items = $dataSection['results'] 
            ?? $dataSection['list'] 
            ?? $dataSection['items'] 
            ?? $dataSection['data'] 
            ?? [];

        // Извлечение total/count
        $total = $dataSection['total'] 
            ?? $dataSection['count'] 
            ?? $dataSection['totalCount']
            ?? $dataSection['blocksCount']
            ?? $dataSection['apartmentsCount']
            ?? count($items);

        return [
            'items' => is_array($items) ? $items : [],
            'total' => (int) $total,
        ];
    }

    /**
     * Нормализовать детальный ответ
     */
    public function normalizeDetailResponse(Response $response): array
    {
        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        if (!is_array($data)) {
            return [];
        }

        // Если есть вложенная структура data
        return $data['data'] ?? $data;
    }
}
