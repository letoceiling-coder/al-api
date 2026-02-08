<?php

namespace App\Console\Commands\TrendAgent;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class AnalyzeDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trendagent:analyze-data
                            {--region=spb : Регион для анализа (spb, msk, и т.д.)}
                            {--output-dir=analysis : Директория для сохранения результатов анализа}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Анализ структуры данных TrendAgent для проектирования БД';

    private string $region;
    private string $basePath;
    private string $outputDir;
    private array $analysis = [
        'field_mapping' => [],
        'common_fields' => [],
        'unique_fields' => [],
        'relationships' => [],
        'data_types' => [],
        'db_schema_proposal' => [],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->region = $this->option('region');
        $this->basePath = storage_path("trendagent/parsing/{$this->region}");
        $this->outputDir = "trendagent/parsing/{$this->region}/{$this->option('output-dir')}";

        $this->info("🔍 Начинаю анализ данных TrendAgent");
        $this->info("📍 Регион: {$this->region}");
        $this->newLine();

        if (!File::exists($this->basePath)) {
            $this->error("❌ Директория с данными не найдена: {$this->basePath}");
            $this->warn("💡 Сначала запустите парсинг: php artisan trendagent:parse --region={$this->region} --type=all --details --save-raw");
            return 1;
        }

        // Анализируем каждый тип объекта
        $types = ['apartments', 'houses', 'plots', 'commercial', 'parkings', 'complexes', 'contractors'];
        
        foreach ($types as $type) {
            $this->info("📊 Анализ типа: {$type}");
            $this->analyzeType($type);
        }

        // Анализируем общие поля
        $this->info("📊 Анализ общих полей...");
        $this->analyzeCommonFields();

        // Анализируем связи
        $this->info("📊 Анализ связей...");
        $this->analyzeRelationships();

        // Генерируем предложение структуры БД
        $this->info("📊 Генерация предложения структуры БД...");
        $this->generateDbSchemaProposal();

        // Сохраняем результаты
        $this->saveAnalysisResults();

        $this->newLine();
        $this->info("✅ Анализ завершён!");
        $this->info("📁 Результаты сохранены в: storage/{$this->outputDir}/");

        return 0;
    }

    /**
     * Анализ данных конкретного типа объекта
     */
    private function analyzeType(string $type): void
    {
        $detailsPath = "{$this->basePath}/details/{$type}";
        
        if (!File::exists($detailsPath)) {
            $this->warn("   ⚠️  Директория не найдена: {$detailsPath}");
            return;
        }

        $files = File::glob("{$detailsPath}/*.json");
        
        if (empty($files)) {
            $this->warn("   ⚠️  Файлы не найдены в: {$detailsPath}");
            return;
        }

        $this->line("   📄 Найдено файлов: " . count($files));
        
        $allFields = [];
        $sampleData = null;
        $filesAnalyzed = 0;
        $maxFiles = min(10, count($files)); // Анализируем максимум 10 файлов для производительности

        foreach (array_slice($files, 0, $maxFiles) as $file) {
            try {
                $content = json_decode(File::get($file), true);
                
                if (!isset($content['data'])) {
                    continue;
                }

                $data = $content['data'];
                
                if ($sampleData === null) {
                    $sampleData = $data;
                }

                $this->extractFields($data, '', $allFields);
                $filesAnalyzed++;
            } catch (\Exception $e) {
                $this->warn("   ⚠️  Ошибка при чтении файла {$file}: " . $e->getMessage());
            }
        }

        if ($filesAnalyzed === 0) {
            $this->warn("   ⚠️  Не удалось проанализировать файлы");
            return;
        }

        // Сохраняем результаты анализа для этого типа
        $this->analysis['field_mapping'][$type] = [
            'total_files' => count($files),
            'files_analyzed' => $filesAnalyzed,
            'fields' => $this->normalizeFields($allFields),
            'sample_structure' => $this->getStructure($sampleData),
        ];

        $this->line("   ✅ Проанализировано: {$filesAnalyzed} файлов");
    }

    /**
     * Извлечение всех полей из данных (рекурсивно)
     */
    private function extractFields(array $data, string $prefix, array &$fields): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value)) {
                if (empty($value)) {
                    $fields[$fullKey] = ['type' => 'array', 'empty' => true];
                } elseif (isset($value[0]) && is_array($value[0])) {
                    // Массив объектов
                    $fields[$fullKey] = ['type' => 'array<object>'];
                    $this->extractFields($value[0], $fullKey . '[]', $fields);
                } else {
                    // Ассоциативный массив
                    $fields[$fullKey] = ['type' => 'object'];
                    $this->extractFields($value, $fullKey, $fields);
                }
            } else {
                $type = $this->getFieldType($value);
                if (!isset($fields[$fullKey])) {
                    $fields[$fullKey] = ['type' => $type, 'values' => []];
                }
                
                if ($type === 'string' && strlen((string)$value) < 100) {
                    $fields[$fullKey]['values'][] = $value;
                }
            }
        }
    }

    /**
     * Определение типа поля
     */
    private function getFieldType($value): string
    {
        if (is_null($value)) {
            return 'null';
        }
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_string($value)) {
            // Проверяем, является ли это датой
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                return 'date';
            }
            // Проверяем, является ли это URL
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return 'url';
            }
            return 'string';
        }
        if (is_array($value)) {
            return 'array';
        }
        return 'unknown';
    }

    /**
     * Нормализация полей (удаление дубликатов, группировка)
     */
    private function normalizeFields(array $fields): array
    {
        $normalized = [];
        
        foreach ($fields as $key => $info) {
            $normalized[$key] = [
                'type' => $info['type'],
                'nullable' => $info['type'] === 'null' || (isset($info['empty']) && $info['empty']),
            ];
            
            if (isset($info['values']) && !empty($info['values'])) {
                $uniqueValues = array_unique($info['values']);
                if (count($uniqueValues) <= 10) {
                    $normalized[$key]['sample_values'] = array_slice($uniqueValues, 0, 10);
                }
            }
        }
        
        return $normalized;
    }

    /**
     * Получение структуры данных (упрощенная версия)
     */
    private function getStructure($data, int $depth = 0): array
    {
        if ($depth > 3) {
            return ['...' => 'max_depth'];
        }

        $structure = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (empty($value)) {
                    $structure[$key] = [];
                } elseif (isset($value[0]) && is_array($value[0])) {
                    $structure[$key] = ['array' => $this->getStructure($value[0], $depth + 1)];
                } else {
                    $structure[$key] = $this->getStructure($value, $depth + 1);
                }
            } else {
                $structure[$key] = $this->getFieldType($value);
            }
        }
        
        return $structure;
    }

    /**
     * Анализ общих полей между типами
     */
    private function analyzeCommonFields(): void
    {
        $allFields = [];
        
        foreach ($this->analysis['field_mapping'] as $type => $mapping) {
            if (isset($mapping['fields'])) {
                foreach ($mapping['fields'] as $field => $info) {
                    if (!isset($allFields[$field])) {
                        $allFields[$field] = [];
                    }
                    $allFields[$field][] = $type;
                }
            }
        }

        $common = [];
        $unique = [];

        foreach ($allFields as $field => $types) {
            if (count($types) > 1) {
                $common[$field] = [
                    'types' => $types,
                    'count' => count($types),
                ];
            } else {
                $unique[$types[0]][] = $field;
            }
        }

        $this->analysis['common_fields'] = $common;
        
        foreach ($unique as $type => $fields) {
            $this->analysis['unique_fields'][$type] = $fields;
        }
    }

    /**
     * Анализ связей между объектами
     */
    private function analyzeRelationships(): void
    {
        $relationships = [];

        // Анализируем связи на основе полей
        foreach ($this->analysis['field_mapping'] as $type => $mapping) {
            if (!isset($mapping['fields'])) {
                continue;
            }

            $typeRelationships = [];

            foreach ($mapping['fields'] as $field => $info) {
                // Ищем поля, которые могут быть связями
                if (preg_match('/(block|complex|building|section|floor|apartment|parking|house|plot|commercial|contractor|village|settlement|project)[_\-]?id/i', $field)) {
                    $typeRelationships[] = [
                        'field' => $field,
                        'type' => $info['type'],
                        'relationship_type' => $this->detectRelationshipType($field),
                    ];
                }
            }

            if (!empty($typeRelationships)) {
                $relationships[$type] = $typeRelationships;
            }
        }

        $this->analysis['relationships'] = $relationships;
    }

    /**
     * Определение типа связи
     */
    private function detectRelationshipType(string $field): string
    {
        if (preg_match('/block|complex/i', $field)) {
            return 'belongs_to_complex';
        }
        if (preg_match('/building/i', $field)) {
            return 'belongs_to_building';
        }
        if (preg_match('/section/i', $field)) {
            return 'belongs_to_section';
        }
        if (preg_match('/floor/i', $field)) {
            return 'belongs_to_floor';
        }
        if (preg_match('/village|settlement/i', $field)) {
            return 'belongs_to_village';
        }
        if (preg_match('/contractor|project/i', $field)) {
            return 'belongs_to_contractor';
        }
        return 'unknown';
    }

    /**
     * Генерация предложения структуры БД
     */
    private function generateDbSchemaProposal(): void
    {
        $proposal = [
            'tables' => [],
            'relationships' => [],
            'indexes' => [],
        ];

        // Базовые таблицы
        $proposal['tables']['trendagent_regions'] = [
            'fields' => [
                'id' => 'bigIncrements',
                'code' => 'string',
                'name' => 'string',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp',
            ],
        ];

        // Генерируем предложения для каждого типа
        foreach ($this->analysis['field_mapping'] as $type => $mapping) {
            if (!isset($mapping['fields'])) {
                continue;
            }

            $tableName = "trendagent_{$type}";
            $fields = [];

            // Базовые поля
            $fields['id'] = 'bigIncrements';
            $fields['external_id'] = 'string';
            $fields['raw_data'] = 'json';
            $fields['created_at'] = 'timestamp';
            $fields['updated_at'] = 'timestamp';

            // Добавляем поля из анализа
            foreach ($mapping['fields'] as $field => $info) {
                // Пропускаем вложенные поля (они будут в raw_data)
                if (strpos($field, '.') !== false || strpos($field, '[]') !== false) {
                    continue;
                }

                $dbType = $this->mapToDbType($info['type'], $field);
                if ($dbType) {
                    $fields[$field] = $dbType;
                }
            }

            $proposal['tables'][$tableName] = [
                'fields' => $fields,
                'indexes' => $this->suggestIndexes($type, $fields),
            ];
        }

        // Добавляем связи
        foreach ($this->analysis['relationships'] as $type => $rels) {
            foreach ($rels as $rel) {
                $proposal['relationships'][] = [
                    'from_table' => "trendagent_{$type}",
                    'from_field' => $rel['field'],
                    'to_table' => $this->getTargetTable($rel['relationship_type']),
                    'relationship_type' => $rel['relationship_type'],
                ];
            }
        }

        $this->analysis['db_schema_proposal'] = $proposal;
    }

    /**
     * Маппинг типа поля на тип БД
     */
    private function mapToDbType(string $type, string $fieldName): ?string
    {
        // Пропускаем некоторые поля, которые будут в raw_data
        if (in_array($fieldName, ['_id', 'id', 'guid', 'slug'])) {
            return null;
        }

        switch ($type) {
            case 'integer':
                return 'integer';
            case 'float':
                return 'decimal:10,2';
            case 'boolean':
                return 'boolean';
            case 'string':
                if (strlen($fieldName) > 50 || preg_match('/url|image|photo/i', $fieldName)) {
                    return 'text';
                }
                return 'string';
            case 'date':
                return 'date';
            case 'url':
                return 'text';
            default:
                return null; // Сложные типы в raw_data
        }
    }

    /**
     * Предложение индексов
     */
    private function suggestIndexes(string $type, array $fields): array
    {
        $indexes = [];

        // Всегда индексируем external_id
        if (isset($fields['external_id'])) {
            $indexes[] = 'external_id';
        }

        // Индексируем поля для фильтрации
        $filterFields = ['price', 'area', 'floor', 'rooms', 'status', 'finishing'];
        foreach ($filterFields as $field) {
            if (isset($fields[$field])) {
                $indexes[] = $field;
            }
        }

        // Составные индексы для частых запросов
        if (isset($fields['complex_id']) && isset($fields['status'])) {
            $indexes[] = ['complex_id', 'status'];
        }

        return $indexes;
    }

    /**
     * Получение целевой таблицы для связи
     */
    private function getTargetTable(string $relationshipType): string
    {
        $mapping = [
            'belongs_to_complex' => 'trendagent_complexes',
            'belongs_to_building' => 'trendagent_buildings',
            'belongs_to_section' => 'trendagent_sections',
            'belongs_to_floor' => 'trendagent_floors',
            'belongs_to_village' => 'trendagent_plot_settlements',
            'belongs_to_contractor' => 'trendagent_contractors',
        ];

        return $mapping[$relationshipType] ?? 'unknown';
    }

    /**
     * Сохранение результатов анализа
     */
    private function saveAnalysisResults(): void
    {
        // Создаем директорию, если её нет
        Storage::makeDirectory($this->outputDir);

        // Сохраняем каждый раздел анализа
        Storage::put(
            "{$this->outputDir}/field_mapping.json",
            json_encode($this->analysis['field_mapping'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        Storage::put(
            "{$this->outputDir}/common_fields.json",
            json_encode($this->analysis['common_fields'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        Storage::put(
            "{$this->outputDir}/unique_fields.json",
            json_encode($this->analysis['unique_fields'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        Storage::put(
            "{$this->outputDir}/relationships.json",
            json_encode($this->analysis['relationships'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        Storage::put(
            "{$this->outputDir}/db_schema_proposal.json",
            json_encode($this->analysis['db_schema_proposal'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // Сохраняем полный отчет
        Storage::put(
            "{$this->outputDir}/full_analysis.json",
            json_encode($this->analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
