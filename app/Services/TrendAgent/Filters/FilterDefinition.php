<?php

namespace App\Services\TrendAgent\Filters;

/**
 * Определение одного фильтра
 * 
 * Описывает:
 * - Тип фильтра (range, select, multiselect, boolean)
 * - Параметры (min/max, options, и т.д.)
 * - Правила валидации
 */
class FilterDefinition
{
    /**
     * @param string $key Ключ фильтра (для API)
     * @param string $type Тип фильтра (range, select, multiselect, boolean)
     * @param string $label Человекочитаемое название
     * @param array $config Конфигурация фильтра
     * @param array $objectTypes Типы объектов, к которым применим
     */
    public function __construct(
        public readonly string $key,
        public readonly string $type,
        public readonly string $label,
        public readonly array $config = [],
        public readonly array $objectTypes = []
    ) {}

    /**
     * Проверить, применим ли фильтр к типу объекта
     */
    public function isApplicableTo(string $objectType): bool
    {
        // Если массив пустой — применим ко всем
        if (empty($this->objectTypes)) {
            return true;
        }

        return in_array($objectType, $this->objectTypes, true);
    }

    /**
     * Валидировать значение фильтра
     */
    public function validate(mixed $value): bool
    {
        return match($this->type) {
            'range' => $this->validateRange($value),
            'select' => $this->validateSelect($value),
            'multiselect' => $this->validateMultiselect($value),
            'boolean' => $this->validateBoolean($value),
            default => false,
        };
    }

    /**
     * Валидация range фильтра
     * 
     * Формат: ['from' => N, 'to' => M] или ['price_from' => N, 'price_to' => M]
     */
    private function validateRange(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        $from = $value['from'] ?? $value[$this->key . '_from'] ?? null;
        $to = $value['to'] ?? $value[$this->key . '_to'] ?? null;

        if ($from !== null && !is_numeric($from)) {
            return false;
        }

        if ($to !== null && !is_numeric($to)) {
            return false;
        }

        return true;
    }

    /**
     * Валидация select фильтра
     */
    private function validateSelect(mixed $value): bool
    {
        return is_string($value) || is_numeric($value);
    }

    /**
     * Валидация multiselect фильтра
     */
    private function validateMultiselect(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $v) {
            if (!is_string($v) && !is_numeric($v)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Валидация boolean фильтра
     */
    private function validateBoolean(mixed $value): bool
    {
        return is_bool($value) || $value === 'true' || $value === 'false' || $value === '1' || $value === '0';
    }

    /**
     * Получить конфигурационный параметр
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
