<?php

namespace App\Services\TrendAgent\Core\Contracts;

/**
 * Унифицированный контракт ответа для списков объектов
 * 
 * Нормализует разные форматы API в единый внутренний формат
 */
class CatalogResult
{
    /**
     * @param array $items Список объектов
     * @param int $total Общее количество объектов (для пагинации)
     * @param array $pagination Информация о пагинации
     * @param array $appliedFilters Примененные фильтры
     * @param array $meta Метаданные (время выполнения, источник, и т.д.)
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly array $pagination,
        public readonly array $appliedFilters,
        public readonly array $meta = []
    ) {}

    /**
     * Проверить, есть ли следующая страница
     */
    public function hasMore(): bool
    {
        $offset = $this->pagination['offset'] ?? 0;
        $count = $this->pagination['count'] ?? 0;
        
        return ($offset + $count) < $this->total;
    }

    /**
     * Получить номер текущей страницы
     */
    public function getCurrentPage(): int
    {
        $count = $this->pagination['count'] ?? 1;
        $offset = $this->pagination['offset'] ?? 0;
        
        return (int) floor($offset / $count) + 1;
    }

    /**
     * Получить общее количество страниц
     */
    public function getTotalPages(): int
    {
        $count = $this->pagination['count'] ?? 1;
        
        return (int) ceil($this->total / $count);
    }

    /**
     * Получить количество элементов на текущей странице
     */
    public function getItemsCount(): int
    {
        return count($this->items);
    }

    /**
     * Проверить, список пустой
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Преобразовать в массив
     */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'total' => $this->total,
            'pagination' => $this->pagination,
            'appliedFilters' => $this->appliedFilters,
            'meta' => $this->meta,
        ];
    }
}
