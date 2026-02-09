<?php

namespace App\Services\TrendAgent\Filters;

use App\Services\TrendAgent\Core\ObjectType;
use App\Services\TrendAgent\Core\Contracts\FilterSet;
use App\Services\TrendAgent\Core\Errors\InvalidFilterError;

/**
 * Унифицированный построитель фильтров
 * 
 * Ответственность:
 * - Создание FilterSet для типа объекта
 * - Валидация фильтров
 * - Применение фильтров
 * 
 * ЕДИНЫЙ ПОСТРОИТЕЛЬ ДЛЯ ВСЕХ ТИПОВ ОБЪЕКТОВ
 */
class FilterBuilder
{
    public function __construct(
        private readonly FilterRegistry $registry
    ) {}

    /**
     * Создать пустой набор фильтров
     */
    public function create(ObjectType $objectType): FilterSet
    {
        return new FilterSet($objectType);
    }

    /**
     * Создать набор фильтров из массива
     * 
     * @throws InvalidFilterError если фильтр невалиден
     */
    public function createFromArray(ObjectType $objectType, array $filters): FilterSet
    {
        $filterSet = $this->create($objectType);

        foreach ($filters as $key => $value) {
            $this->addFilter($filterSet, $key, $value);
        }

        return $filterSet;
    }

    /**
     * Добавить фильтр
     * 
     * @throws InvalidFilterError если фильтр невалиден
     */
    public function addFilter(FilterSet $filterSet, string $key, mixed $value): FilterSet
    {
        // Получить определение фильтра
        $definition = $this->registry->get($key);

        if ($definition === null) {
            // Если фильтр не зарегистрирован, добавляем как есть (для обратной совместимости)
            $filterSet->add($key, $value);
            return $filterSet;
        }

        // Проверить применимость к типу объекта
        if (!$definition->isApplicableTo($filterSet->objectType->value)) {
            throw new InvalidFilterError(
                "Filter '{$key}' is not applicable to object type '{$filterSet->objectType->value}'",
                context: ['filter' => $key, 'objectType' => $filterSet->objectType->value]
            );
        }

        // Валидировать значение
        if (!$definition->validate($value)) {
            throw new InvalidFilterError(
                "Invalid value for filter '{$key}'",
                context: ['filter' => $key, 'value' => $value, 'type' => $definition->type]
            );
        }

        // Преобразовать range фильтры в формат API (price_from, price_to)
        if ($definition->type === 'range' && is_array($value)) {
            $value = $this->normalizeRangeFilter($key, $value);
        }

        // Добавить фильтр
        $filterSet->add($key, $value);

        return $filterSet;
    }

    /**
     * Нормализовать range фильтр в формат API
     * 
     * Преобразует ['from' => 1000000, 'to' => 5000000] 
     * в ['price_from' => 1000000, 'price_to' => 5000000]
     */
    private function normalizeRangeFilter(string $key, array $value): array
    {
        $normalized = [];

        if (isset($value['from'])) {
            $normalized[$key . '_from'] = $value['from'];
        } elseif (isset($value[$key . '_from'])) {
            $normalized[$key . '_from'] = $value[$key . '_from'];
        }

        if (isset($value['to'])) {
            $normalized[$key . '_to'] = $value['to'];
        } elseif (isset($value[$key . '_to'])) {
            $normalized[$key . '_to'] = $value[$key . '_to'];
        }

        return $normalized;
    }

    /**
     * Удалить фильтр
     */
    public function removeFilter(FilterSet $filterSet, string $key): FilterSet
    {
        $filterSet->remove($key);
        return $filterSet;
    }

    /**
     * Получить все доступные фильтры для типа объекта
     * 
     * @return array<string, FilterDefinition>
     */
    public function getAvailableFilters(ObjectType $objectType): array
    {
        return $this->registry->getForObjectType($objectType);
    }

    /**
     * Валидировать набор фильтров
     * 
     * @throws InvalidFilterError если хотя бы один фильтр невалиден
     */
    public function validate(FilterSet $filterSet): bool
    {
        foreach ($filterSet->all() as $key => $value) {
            $definition = $this->registry->get($key);

            if ($definition === null) {
                // Неизвестный фильтр - пропускаем (для обратной совместимости)
                continue;
            }

            if (!$definition->validate($value)) {
                throw new InvalidFilterError(
                    "Invalid value for filter '{$key}'",
                    context: ['filter' => $key, 'value' => $value]
                );
            }
        }

        return true;
    }

    /**
     * Преобразовать FilterSet в query параметры для API
     */
    public function toQueryParams(FilterSet $filterSet): array
    {
        $params = [];

        foreach ($filterSet->all() as $key => $value) {
            // Если это range фильтр в нормализованном формате
            if (is_array($value) && isset($value[$key . '_from'])) {
                // Уже в формате price_from, price_to
                $params = array_merge($params, $value);
            } elseif (is_array($value) && isset($value['from'])) {
                // Преобразуем в price_from, price_to
                if (isset($value['from'])) {
                    $params[$key . '_from'] = $value['from'];
                }
                if (isset($value['to'])) {
                    $params[$key . '_to'] = $value['to'];
                }
            } elseif (is_array($value)) {
                // Множественные значения (room, и т.д.)
                $params[$key] = $value;
            } else {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
