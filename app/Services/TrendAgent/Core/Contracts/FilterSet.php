<?php

namespace App\Services\TrendAgent\Core\Contracts;

use App\Services\TrendAgent\Core\ObjectType;

/**
 * Набор активных фильтров для поиска
 * 
 * Хранит, валидирует и сериализует фильтры в query-параметры
 */
class FilterSet
{
    private array $filters = [];

    public function __construct(
        public readonly ObjectType $objectType,
        array $filters = []
    ) {
        $this->filters = $filters;
    }

    /**
     * Добавить фильтр
     */
    public function add(string $key, mixed $value): self
    {
        $this->filters[$key] = $value;
        return $this;
    }

    /**
     * Удалить фильтр
     */
    public function remove(string $key): self
    {
        unset($this->filters[$key]);
        return $this;
    }

    /**
     * Проверить, установлен ли фильтр
     */
    public function has(string $key): bool
    {
        return isset($this->filters[$key]);
    }

    /**
     * Получить значение фильтра
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    /**
     * Получить все фильтры
     */
    public function all(): array
    {
        return $this->filters;
    }

    /**
     * Проверить, есть ли активные фильтры
     */
    public function isEmpty(): bool
    {
        return empty($this->filters);
    }

    /**
     * Получить количество фильтров
     */
    public function count(): int
    {
        return count($this->filters);
    }
}
