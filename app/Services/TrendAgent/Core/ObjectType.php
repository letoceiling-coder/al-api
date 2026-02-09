<?php

namespace App\Services\TrendAgent\Core;

/**
 * Типы объектов недвижимости в системе TrendAgent
 * 
 * Используется для:
 * - Маршрутизации на правильный API домен
 * - Определения доступных фильтров
 * - Выбора стратегии агрегации данных
 */
enum ObjectType: string
{
    case BLOCKS = 'blocks';                    // Жилые комплексы (ЖК)
    case APARTMENTS = 'apartments';            // Квартиры
    case PARKING = 'parking';                  // Паркинги (машиноместа)
    case HOUSES = 'houses';                    // Дома (коттеджи + таунхаусы)
    case PLOTS = 'plots';                      // Участки
    case COMMERCE = 'commerce';                // Коммерческая недвижимость
    case HOUSE_PROJECTS = 'house_projects';    // Проекты домов
    case VILLAGES = 'villages';                // Поселки

    /**
     * Получить человекочитаемое название типа
     */
    public function getLabel(): string
    {
        return match($this) {
            self::BLOCKS => 'Жилые комплексы',
            self::APARTMENTS => 'Квартиры',
            self::PARKING => 'Паркинги',
            self::HOUSES => 'Дома',
            self::PLOTS => 'Участки',
            self::COMMERCE => 'Коммерция',
            self::HOUSE_PROJECTS => 'Проекты домов',
            self::VILLAGES => 'Поселки',
        };
    }

    /**
     * Проверить, является ли тип каталожным (имеет списки)
     */
    public function isCatalogType(): bool
    {
        return true; // Все типы имеют каталоги
    }

    /**
     * Проверить, имеет ли тип детальную страницу
     */
    public function hasDetailPage(): bool
    {
        return true; // Все типы имеют детальные страницы
    }

    /**
     * Получить все доступные типы
     * 
     * @return array<ObjectType>
     */
    public static function all(): array
    {
        return [
            self::BLOCKS,
            self::APARTMENTS,
            self::PARKING,
            self::HOUSES,
            self::PLOTS,
            self::COMMERCE,
            self::HOUSE_PROJECTS,
            self::VILLAGES,
        ];
    }
}
