<?php

namespace App\Services\TrendAgent;

use App\Services\TrendAgent\Core\ObjectType;
use App\Services\TrendAgent\Catalog\CatalogService;
use App\Services\TrendAgent\Detail\DetailService;
use App\Services\TrendAgent\Core\Errors\NotFoundError;

/**
 * Сервис тестовой выгрузки образцов данных по всем типам объектов.
 * Возвращает структуру каталога и деталей для анализа полей API.
 */
class SampleDataService
{
    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly DetailService $detailService
    ) {}

    /**
     * Собрать образцы по всем типам объектов.
     *
     * @param string $cityKey Код города (spb, msk, ...)
     * @param int $perType Количество элементов каталога на тип
     * @param bool $fetchDetail Запрашивать детали по первому элементу
     * @return array<string, array> [objectType => summary]
     */
    public function fetchAllTypes(string $cityKey, int $perType = 3, bool $fetchDetail = true): array
    {
        $results = [];

        foreach (ObjectType::all() as $objectType) {
            $results[$objectType->value] = $this->fetchOneType($objectType, $cityKey, $perType, $fetchDetail);
        }

        return $results;
    }

    /**
     * Собрать образец по одному типу.
     */
    public function fetchOneType(
        ObjectType $objectType,
        string $cityKey,
        int $perType = 3,
        bool $fetchDetail = true
    ): array {
        $summary = [
            'type' => $objectType->value,
            'label' => $objectType->getLabel(),
            'catalog' => [
                'total' => 0,
                'requested_count' => $perType,
                'items_count' => 0,
                'first_item_keys' => [],
                'first_item_sample' => null,
                'error' => null,
            ],
            'detail' => [
                'requested_id' => null,
                'entity_keys' => [],
                'entity_sample' => null,
                'related_keys' => [],
                'media' => ['photos' => 0, 'videos' => 0, 'documents' => 0, 'floorPlans' => 0, 'tours3D' => 0],
                'error' => null,
            ],
        ];

        try {
            $catalog = $this->catalogService->getCatalog(
                $objectType,
                $cityKey,
                null,
                1,
                $perType
            );

            $summary['catalog']['total'] = $catalog->total;
            $summary['catalog']['items_count'] = count($catalog->items);

            if (!empty($catalog->items)) {
                $first = $catalog->items[0];
                $firstArr = is_object($first) ? (array) $first : $first;
                $summary['catalog']['first_item_keys'] = array_keys($firstArr);
                $summary['catalog']['first_item_sample'] = $this->sampleValues($firstArr, 200);
            }

            if ($fetchDetail && !empty($catalog->items)) {
                $first = $catalog->items[0];
                $firstArr = is_object($first) ? (array) $first : $first;
                $id = $firstArr['_id'] ?? $firstArr['id'] ?? null;
                if ($id) {
                    $summary['detail']['requested_id'] = $id;
                    try {
                        $detail = $this->detailService->getDetail($objectType, (string) $id, $cityKey);
                        $entity = $detail->entity;
                        $entityArr = is_object($entity) ? (array) $entity : (is_array($entity) ? $entity : []);
                        $summary['detail']['entity_keys'] = array_keys($entityArr);
                        $summary['detail']['entity_sample'] = $this->sampleValues($entityArr, 300);
                        $summary['detail']['related_keys'] = array_keys($detail->related);
                        $summary['detail']['media'] = [
                            'photos' => count($detail->media->photos ?? []),
                            'videos' => count($detail->media->videos ?? []),
                            'documents' => count($detail->media->documents ?? []),
                            'floorPlans' => count($detail->media->floorPlans ?? []),
                            'tours3D' => count($detail->media->tours3D ?? []),
                        ];
                    } catch (NotFoundError $e) {
                        $summary['detail']['error'] = 'Not found: ' . $e->getMessage();
                    } catch (\Throwable $e) {
                        $summary['detail']['error'] = $e->getMessage();
                    }
                } else {
                    $summary['detail']['error'] = 'No _id/id in catalog item';
                }
            }
        } catch (\Throwable $e) {
            $summary['catalog']['error'] = $e->getMessage();
        }

        return $summary;
    }

    private function sampleValues(array $data, int $maxLen = 200): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $out[$k] = '(array[' . count((array) $v) . '])';
            } else {
                $s = (string) $v;
                $out[$k] = strlen($s) > 80 ? substr($s, 0, 77) . '...' : $s;
            }
        }
        $encoded = json_encode($out, JSON_UNESCAPED_UNICODE);
        if ($encoded && strlen($encoded) > $maxLen) {
            return array_slice($out, 0, 15);
        }
        return $out;
    }
}
