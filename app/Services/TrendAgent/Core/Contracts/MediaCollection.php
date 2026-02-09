<?php

namespace App\Services\TrendAgent\Core\Contracts;

/**
 * Коллекция медиа контента
 * 
 * Хранит фото, видео, документы, 3D туры, планировки
 */
class MediaCollection
{
    /**
     * @param array $photos Фотографии
     * @param array $videos Видео
     * @param array $documents Документы
     * @param array $tours3D 3D туры
     * @param array $floorPlans Планировки
     * @param array $other Другое
     */
    public function __construct(
        public readonly array $photos = [],
        public readonly array $videos = [],
        public readonly array $documents = [],
        public readonly array $tours3D = [],
        public readonly array $floorPlans = [],
        public readonly array $other = []
    ) {}

    /**
     * Получить общее количество медиа
     */
    public function getTotalCount(): int
    {
        return count($this->photos)
            + count($this->videos)
            + count($this->documents)
            + count($this->tours3D)
            + count($this->floorPlans)
            + count($this->other);
    }

    /**
     * Проверить, пустая ли коллекция
     */
    public function isEmpty(): bool
    {
        return $this->getTotalCount() === 0;
    }

    /**
     * Получить все медиа в одном массиве
     */
    public function getAll(): array
    {
        return array_merge(
            $this->photos,
            $this->videos,
            $this->documents,
            $this->tours3D,
            $this->floorPlans,
            $this->other
        );
    }

    /**
     * Преобразовать в массив
     */
    public function toArray(): array
    {
        return [
            'photos' => $this->photos,
            'videos' => $this->videos,
            'documents' => $this->documents,
            'tours3D' => $this->tours3D,
            'floorPlans' => $this->floorPlans,
            'other' => $this->other,
            'total' => $this->getTotalCount(),
        ];
    }
}
