<?php

namespace App\Services\TrendAgent\Catalog;

/**
 * Менеджер пагинации
 * 
 * Ответственность:
 * - Вычисление offset/count
 * - Создание pagination metadata
 * - Валидация параметров
 */
class PaginationManager
{
    private const DEFAULT_PAGE_SIZE = 20;
    private const MAX_PAGE_SIZE = 100;

    /**
     * Создать параметры пагинации для API
     * 
     * @param int $page Номер страницы (1-based)
     * @param int|null $pageSize Размер страницы
     * @return array ['offset' => N, 'count' => M]
     */
    public function createParams(int $page = 1, ?int $pageSize = null): array
    {
        $pageSize = $this->validatePageSize($pageSize ?? self::DEFAULT_PAGE_SIZE);
        $page = max(1, $page);

        return [
            'offset' => ($page - 1) * $pageSize,
            'count' => $pageSize,
        ];
    }

    /**
     * Создать metadata пагинации из ответа API
     * 
     * @param int $total Общее количество элементов
     * @param int $offset Текущий offset
     * @param int $count Размер страницы
     * @return array Metadata пагинации
     */
    public function createMetadata(int $total, int $offset, int $count): array
    {
        $currentPage = (int) floor($offset / $count) + 1;
        $totalPages = (int) ceil($total / $count);
        $hasMore = ($offset + $count) < $total;

        return [
            'offset' => $offset,
            'count' => $count,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'total' => $total,
            'hasMore' => $hasMore,
            'from' => $offset + 1,
            'to' => min($offset + $count, $total),
        ];
    }

    /**
     * Валидировать размер страницы
     */
    private function validatePageSize(int $pageSize): int
    {
        return min(max(1, $pageSize), self::MAX_PAGE_SIZE);
    }

    /**
     * Получить параметры для следующей страницы
     */
    public function getNextPageParams(array $currentPagination): ?array
    {
        if (!($currentPagination['hasMore'] ?? false)) {
            return null;
        }

        return [
            'offset' => $currentPagination['offset'] + $currentPagination['count'],
            'count' => $currentPagination['count'],
        ];
    }

    /**
     * Получить параметры для предыдущей страницы
     */
    public function getPrevPageParams(array $currentPagination): ?array
    {
        $offset = $currentPagination['offset'] ?? 0;

        if ($offset <= 0) {
            return null;
        }

        $count = $currentPagination['count'] ?? self::DEFAULT_PAGE_SIZE;
        $newOffset = max(0, $offset - $count);

        return [
            'offset' => $newOffset,
            'count' => $count,
        ];
    }
}
