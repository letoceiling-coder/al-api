<?php

namespace App\Services\TrendAgent\Detail;

use App\Services\TrendAgent\Core\ObjectType;
use App\Services\TrendAgent\Core\Contracts\DetailResult;
use App\Services\TrendAgent\Core\Contracts\MediaCollection;
use App\Services\TrendAgent\Core\Errors\NotFoundError;
use App\Services\TrendAgent\TrendAgentApiClient;
use App\Services\TrendAgent\Http\ResponseNormalizer;
use Illuminate\Support\Facades\Log;

/**
 * Унифицированный сервис для получения детальной информации
 * 
 * ЕДИНАЯ ТОЧКА ВХОДА ДЛЯ ВСЕХ ДЕТАЛЕЙ:
 * - Блоки (ЖК)
 * - Квартиры
 * - Паркинги
 * - Дома
 * - Участки
 * - Коммерция
 * - Проекты домов
 * 
 * Использует существующий TrendAgentApiClient для обратной совместимости
 */
class DetailService
{
    public function __construct(
        private readonly TrendAgentApiClient $apiClient,
        private readonly ResponseNormalizer $normalizer
    ) {}

    /**
     * Получить детальную информацию по ID
     * 
     * @param ObjectType $objectType Тип объекта
     * @param string $id ID объекта
     * @param string $city ID города
     * @return DetailResult
     * @throws NotFoundError если объект не найден
     */
    public function getDetail(
        ObjectType $objectType,
        string $id,
        string $city
    ): DetailResult {
        try {
            // Вызвать соответствующий метод API клиента
            $result = $this->callApiMethod($objectType, $id, $city);

            // Нормализовать ответ
            $entity = $this->normalizeEntity($result, $objectType);

            // Извлечь медиа
            $media = $this->extractMedia($result);

            // Извлечь связанные данные
            $related = $this->extractRelated($result, $objectType);

            return new DetailResult(
                entity: $entity,
                media: $media,
                related: $related,
                dictionariesUsed: [],
                meta: [
                    'objectType' => $objectType->value,
                    'id' => $id,
                    'city' => $city,
                ]
            );

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'not found')) {
                throw new NotFoundError(
                    "Object not found: {$id}",
                    context: ['objectType' => $objectType->value, 'id' => $id]
                );
            }

            Log::error('DetailService: Ошибка получения деталей', [
                'objectType' => $objectType->value,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Вызвать соответствующий метод API клиента
     */
    private function callApiMethod(ObjectType $objectType, string $id, string $city): array
    {
        $params = ['city' => $city];

        return match($objectType) {
            ObjectType::BLOCKS => $this->getBlockDetails($id, $params),
            ObjectType::APARTMENTS => $this->getApartmentDetails($id, $params),
            ObjectType::PARKING => $this->apiClient->getParkingDetails($id, $params),
            ObjectType::HOUSES => $this->apiClient->getHouseDetails($id, $params),
            ObjectType::PLOTS => $this->apiClient->getPlotDetails($id, $params),
            ObjectType::COMMERCE => $this->apiClient->getCommercialDetails($id, $params),
            ObjectType::HOUSE_PROJECTS => $this->apiClient->getContractorProjectDetails($id, $params),
            default => throw new \InvalidArgumentException("Unsupported object type: {$objectType->value}"),
        };
    }

    /**
     * Получить детали блока (ЖК)
     */
    private function getBlockDetails(string $id, array $params): array
    {
        // getApartmentDetails на самом деле получает детали блока
        $result = $this->apiClient->getApartmentDetails($id, $params);
        
        return [
            'success' => true,
            'data' => $result['data'] ?? $result,
        ];
    }

    /**
     * Получить детали квартиры
     */
    private function getApartmentDetails(string $id, array $params): array
    {
        // Для квартир используем getApartmentFlatDetails или другой метод
        // Пока используем getApartmentDetails, но это может быть неправильно
        // TODO: Найти правильный метод для получения деталей квартиры
        try {
            $result = $this->apiClient->getApartmentDetails($id, $params);
            return [
                'success' => true,
                'data' => $result['data'] ?? $result,
            ];
        } catch (\Exception $e) {
            // Если не получилось, возвращаем пустой результат
            Log::warning('DetailService: Не удалось получить детали квартиры', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'data' => [],
            ];
        }
    }

    /**
     * Нормализовать сущность из ответа API
     */
    private function normalizeEntity(array $result, ObjectType $objectType): array
    {
        // Если есть вложенная структура data
        if (isset($result['data'])) {
            return $result['data'];
        }

        // Если результат уже является сущностью
        return $result;
    }

    /**
     * Извлечь медиа из ответа
     */
    private function extractMedia(array $result): MediaCollection
    {
        $data = $result['data'] ?? $result;

        return new MediaCollection(
            photos: $data['photos'] ?? $data['images'] ?? [],
            videos: $data['videos'] ?? [],
            documents: $data['documents'] ?? [],
            tours3D: $data['tours3D'] ?? $data['tours_3d'] ?? [],
            floorPlans: $data['floorPlans'] ?? $data['plans'] ?? $data['floor_plans'] ?? [],
            other: $data['other'] ?? []
        );
    }

    /**
     * Извлечь связанные данные
     */
    private function extractRelated(array $result, ObjectType $objectType): array
    {
        $data = $result['data'] ?? $result;
        $related = [];

        // Для блоков (ЖК) - дополнительные данные
        if ($objectType === ObjectType::BLOCKS) {
            $related = [
                'advantages' => $data['advantages'] ?? [],
                'infrastructure' => $data['infrastructure'] ?? [],
                'banks' => $data['banks'] ?? [],
                'mortgage' => $data['mortgage'] ?? [],
                'nearby_places' => $data['nearby_places'] ?? [],
            ];
        }

        // Для квартир - связанные данные
        if ($objectType === ObjectType::APARTMENTS) {
            $related = [
                'block' => $data['block'] ?? null,
                'building' => $data['building'] ?? null,
                'floor_plan' => $data['floor_plan'] ?? null,
            ];
        }

        return $related;
    }
}
