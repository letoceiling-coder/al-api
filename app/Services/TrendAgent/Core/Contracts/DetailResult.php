<?php

namespace App\Services\TrendAgent\Core\Contracts;

/**
 * Унифицированный контракт ответа для детальной информации
 * 
 * Агрегирует данные из множественных endpoint'ов в единую структуру
 */
class DetailResult
{
    /**
     * @param mixed $entity Основная сущность
     * @param MediaCollection $media Коллекция медиа (фото, видео, документы)
     * @param array $related Связанные данные (инфраструктура, преимущества, банки, и т.д.)
     * @param array $dictionariesUsed Использованные справочники
     * @param array $meta Метаданные (источники, время агрегации, и т.д.)
     */
    public function __construct(
        public readonly mixed $entity,
        public readonly MediaCollection $media,
        public readonly array $related,
        public readonly array $dictionariesUsed,
        public readonly array $meta = []
    ) {}

    /**
     * Проверить, все ли данные успешно загружены
     */
    public function isComplete(): bool
    {
        return empty($this->meta['failedEndpoints'] ?? []);
    }

    /**
     * Получить список неудачных endpoint'ов
     */
    public function getFailedEndpoints(): array
    {
        return $this->meta['failedEndpoints'] ?? [];
    }

    /**
     * Проверить, есть ли медиа
     */
    public function hasMedia(): bool
    {
        return !$this->media->isEmpty();
    }

    /**
     * Преобразовать в массив
     */
    public function toArray(): array
    {
        return [
            'entity' => $this->entity,
            'media' => $this->media->toArray(),
            'related' => $this->related,
            'dictionariesUsed' => $this->dictionariesUsed,
            'meta' => $this->meta,
        ];
    }
}
