<?php

namespace App\Services\TrendAgent\Catalog;

use App\Services\TrendAgent\Core\ObjectType;
use App\Services\TrendAgent\TrendAgentApiClient;
use App\Services\TrendAgent\Catalog\PaginationManager;
use App\Services\TrendAgent\Http\ResponseNormalizer;
use Illuminate\Support\Facades\Log;

/**
 * Унифицированный сервис для получения каталогов
 * 
 * ЕДИНАЯ ТОЧКА ВХОДА ДЛЯ ВСЕХ СПИСКОВ:
 * - Блоки (ЖК)
 * - Квартиры
 * - Паркинги
 * - Дома
 * - Участки
 * - Коммерция
 * - Проекты домов
 * - Поселки
 * 
 * Использует существующий TrendAgentApiClient для обратной совместимости
 */
class CatalogService
{
    public function __construct(
        private readonly TrendAgentApiClient $apiClient,
        private readonly PaginationManager $paginationManager,
        private readonly ResponseNormalizer $normalizer
    ) {}

    /**
     * Получить список объектов
     * 
     * @param ObjectType $objectType Тип объекта
     * @param string $city ID города
     * @param array $filters Фильтры (price_from, price_to, room, и т.д.)
     * @param int $page Номер страницы
     * @param int|null $pageSize Размер страницы
     * @param string|null $sort Сортировка
     * @param string|null $sortOrder Порядок сортировки (asc/desc)
     * @return array Массив с items, total, pagination
     */
    public function getCatalog(
        ObjectType $objectType,
        string $city,
        array $filters = [],
        int $page = 1,
        ?int $pageSize = null,
        ?string $sort = 'price',
        ?string $sortOrder = 'asc'
    ): array {
        // Создать параметры пагинации
        $paginationParams = $this->paginationManager->createParams($page, $pageSize);

        // Объединить параметры
        $params = array_merge($paginationParams, $filters);
        $params['sort'] = $sort ?? 'price';
        $params['sort_order'] = $sortOrder ?? 'asc';
        $params['city'] = $city;

        try {
            // Вызвать соответствующий метод API клиента
            $result = $this->callApiMethod($objectType, $params);

            // Нормализовать ответ
            $normalized = $this->normalizeResult($result, $objectType);

            // Создать pagination metadata
            $pagination = $this->paginationManager->createMetadata(
                $normalized['total'],
                $paginationParams['offset'],
                $paginationParams['count']
            );

            return [
                'items' => $normalized['items'],
                'total' => $normalized['total'],
                'pagination' => $pagination,
                'appliedFilters' => $filters,
                'meta' => [
                    'objectType' => $objectType->value,
                    'city' => $city,
                    'sort' => $sort,
                    'sortOrder' => $sortOrder,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('CatalogService: Ошибка получения каталога', [
                'objectType' => $objectType->value,
                'city' => $city,
                'error' => $e->getMessage(),
            ]);

            return [
                'items' => [],
                'total' => 0,
                'pagination' => $this->paginationManager->createMetadata(0, 0, $paginationParams['count']),
                'appliedFilters' => $filters,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Вызвать соответствующий метод API клиента
     */
    private function callApiMethod(ObjectType $objectType, array $params): array
    {
        return match($objectType) {
            ObjectType::BLOCKS => $this->getBlocks($params),
            ObjectType::APARTMENTS => $this->apiClient->getApartments($params),
            ObjectType::PARKING => $this->apiClient->getParkings($params),
            ObjectType::HOUSES => $this->apiClient->getHouses($params),
            ObjectType::PLOTS => $this->apiClient->getPlots($params),
            ObjectType::COMMERCE => $this->apiClient->getCommercial($params),
            ObjectType::HOUSE_PROJECTS => $this->getContractorProjects($params),
            ObjectType::VILLAGES => $this->getVillages($params),
        };
    }

    /**
     * Получить комплексы (ЖК)
     */
    private function getBlocks(array $params): array
    {
        // Используем getObjectsList для получения комплексов
        $city = $params['city'] ?? 'spb';
        $count = $params['count'] ?? 20;
        $offset = $params['offset'] ?? 0;
        
        $result = $this->apiClient->getObjectsList($city, 'block', $count, $offset);
        
        return [
            'success' => true,
            'data' => $result['data'] ?? [],
            'total' => $result['total'] ?? 0,
        ];
    }

    /**
     * Получить проекты домов (подрядчики)
     */
    private function getContractorProjects(array $params): array
    {
        // Используем getContractors для получения проектов
        $result = $this->apiClient->getContractors($params);
        
        return [
            'success' => true,
            'data' => $result['data'] ?? [],
            'total' => $result['total'] ?? count($result['data'] ?? []),
        ];
    }

    /**
     * Получить поселки
     */
    private function getVillages(array $params): array
    {
        // Используем getPlots для получения поселков (villages)
        // В API поселки могут быть частью plots или отдельным endpoint
        $result = $this->apiClient->getPlots($params);
        
        return [
            'success' => true,
            'data' => $result['data'] ?? [],
            'total' => $result['total'] ?? 0,
        ];
    }

    /**
     * Нормализовать результат API
     */
    private function normalizeResult(array $result, ObjectType $objectType): array
    {
        // Если результат уже в правильном формате
        if (isset($result['data']) && isset($result['total'])) {
            return [
                'items' => $result['data'] ?? [],
                'total' => $result['total'] ?? 0,
            ];
        }

        // Если есть вложенная структура data
        if (isset($result['data']['results'])) {
            return [
                'items' => $result['data']['results'] ?? [],
                'total' => $result['data']['blocksCount'] 
                    ?? $result['data']['apartmentsCount'] 
                    ?? count($result['data']['results'] ?? []),
            ];
        }

        // Если это массив результатов
        if (isset($result[0])) {
            return [
                'items' => $result,
                'total' => count($result),
            ];
        }

        // По умолчанию
        return [
            'items' => [],
            'total' => 0,
        ];
    }

    /**
     * Получить количество объектов без загрузки данных
     * 
     * Используется для быстрого получения count с фильтрами
     */
    public function getCount(
        ObjectType $objectType,
        string $city,
        array $filters = []
    ): int {
        // Получаем первую страницу с минимальным count
        $result = $this->getCatalog(
            $objectType,
            $city,
            $filters,
            page: 1,
            pageSize: 1
        );

        return $result['total'] ?? 0;
    }
}
