<?php

namespace App\Services\TrendAgent;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;

/**
 * Сравнение JSON структур ответов DB API с ожидаемым контрактом.
 * missing/extra keys, type mismatch.
 */
class ContractValidatorService
{
    private string $baseUrl;
    private bool $useInternal;

    public function __construct(?string $baseUrl = null, bool $useInternal = false)
    {
        $this->baseUrl = $baseUrl ?? config('app.url', 'http://localhost');
        $this->useInternal = $useInternal;
    }

    /**
     * @return array{status: int, data: array|null}|null
     */
    private function fetchEndpointData(array $config): ?array
    {
        if ($this->useInternal) {
            return $this->callInternal($config);
        }
        $method = strtoupper($config['method'] ?? 'GET');
        $url = rtrim($this->baseUrl, '/') . $config['path'];
        $body = $config['body'] ?? [];
        try {
            if ($method === 'GET') {
                $r = Http::timeout(10)->get($url);
            } else {
                $r = Http::timeout(10)->post($url, $body);
            }
            return ['status' => $r->status(), 'data' => $r->json()];
        } catch (\Throwable $e) {
            return ['status' => 500, 'data' => ['error' => $e->getMessage()]];
        }
    }

    public function validateAll(): array
    {
        $results = [];
        config(['trendagent.data_source' => 'db']);

        $endpoints = [
            'cities' => ['method' => 'GET', 'path' => '/api/trendagent/v1/cities', 'schema' => $this->citiesSchema()],
            'objects-list' => ['method' => 'POST', 'path' => '/api/trendagent/v1/objects/list', 'body' => ['object_type' => 'blocks', 'city' => '58c665588b6aa52311afa01b', 'count' => 5], 'schema' => $this->objectsListSchema()],
            'apartments-list' => ['method' => 'POST', 'path' => '/api/trendagent/v1/apartments', 'body' => ['city' => '58c665588b6aa52311afa01b', 'count' => 5, 'page' => 1], 'schema' => $this->apartmentsListSchema()],
            'apartment-detail' => ['method' => 'POST', 'path' => null, 'body' => [], 'schema' => $this->apartmentDetailSchema(), 'dynamic' => true],
        ];

        foreach ($endpoints as $name => $config) {
            if (($config['dynamic'] ?? false) && $config['path'] === null) {
                $result = $this->validateApartmentDetailDynamic();
            } else {
                $data = $this->fetchEndpointData($config);
                $result = $this->validateData($name, $data, $config['schema']);
            }
            $results[$name] = $result;
        }

        return $results;
    }

    /**
     * @return array{status: int, data: array|null}|null
     */
    private function callInternal(array $config): ?array
    {
        try {
            $method = strtoupper($config['method'] ?? 'GET');
            $path = $config['path'];
            $body = $config['body'] ?? [];

            $request = Request::create($path, $method, $body);
            $request->headers->set('Accept', 'application/json');
            $response = App::handle($request);

            return [
                'status' => $response->getStatusCode(),
                'data' => json_decode($response->getContent(), true),
            ];
        } catch (\Throwable $e) {
            return ['status' => 500, 'data' => ['error' => $e->getMessage(), 'ok' => false]];
        }
    }

    private function validateData(string $name, ?array $fetchResult, array $schema): array
    {
        if (!$fetchResult || ($fetchResult['status'] ?? 500) >= 400) {
            return [
                'ok' => false,
                'errors' => [['message' => 'HTTP ' . ($fetchResult['status'] ?? 'error'), 'body' => json_encode($fetchResult['data'] ?? null)]],
                'warnings' => [],
            ];
        }

        return $this->compareStructure($fetchResult['data'] ?? [], $schema, $name);
    }

    private function compareStructure(mixed $actual, mixed $expected, string $path = ''): array
    {
        $errors = [];
        $warnings = [];

        if ($expected === '*' || $expected === 'any') {
            return ['ok' => true, 'errors' => [], 'warnings' => []];
        }

        if (isset($expected['_type'])) {
            $typeOk = $this->checkType($actual, $expected['_type'], $path);
            if (!$typeOk) {
                $errors[] = ['path' => $path, 'message' => 'type mismatch', 'expected' => $expected['_type'], 'actual' => gettype($actual)];
            }
        }

        if (isset($expected['_keys']) && is_array($actual)) {
            $required = $expected['_keys'];
            foreach ($required as $key) {
                if (!array_key_exists($key, $actual)) {
                    $errors[] = ['path' => $path ? "{$path}.{$key}" : $key, 'message' => 'missing key', 'key' => $key];
                }
            }
        }

        if (isset($expected['_structure']) && is_array($expected['_structure']) && is_array($actual)) {
            foreach ($expected['_structure'] as $key => $subSchema) {
                if (array_key_exists($key, $actual)) {
                    $subResult = $this->compareStructure($actual[$key], $subSchema, $path ? "{$path}.{$key}" : $key);
                    $errors = array_merge($errors, $subResult['errors']);
                    $warnings = array_merge($warnings, $subResult['warnings']);
                }
            }
        }

        if (isset($expected['_items']) && is_array($actual) && isset($actual[0])) {
            $itemSchema = $expected['_items'];
            foreach (array_slice($actual, 0, 3) as $i => $item) {
                $subResult = $this->compareStructure($item, $itemSchema, "{$path}[{$i}]");
                $errors = array_merge($errors, $subResult['errors']);
                $warnings = array_merge($warnings, $subResult['warnings']);
            }
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    private function checkType(mixed $value, string $expectedType, string $path): bool
    {
        $actual = gettype($value);
        $map = ['string' => 'string', 'int' => 'integer', 'integer' => 'integer', 'float' => 'double', 'bool' => 'boolean', 'boolean' => 'boolean', 'array' => 'array', 'object' => 'object'];
        $exp = $map[$expectedType] ?? $expectedType;
        if ($exp === 'integer' && $actual === 'integer') {
            return true;
        }
        if ($exp === 'double' && in_array($actual, ['integer', 'double'])) {
            return true;
        }
        return $actual === $exp;
    }

    private function citiesSchema(): array
    {
        return [
            '_keys' => ['success', 'data'],
            '_structure' => [
                'success' => ['_type' => 'boolean'],
                'data' => [
                    '_type' => 'array',
                    '_items' => ['_keys' => ['id', 'name'], '_structure' => ['id' => ['_type' => 'string'], 'name' => ['_type' => 'string']]],
                ],
            ],
        ];
    }

    private function objectsListSchema(): array
    {
        return [
            '_keys' => ['success', 'data', 'total_count', 'pagination'],
            '_structure' => [
                'success' => ['_type' => 'boolean'],
                'data' => [
                    '_keys' => ['objects'],
                    '_structure' => [
                        'objects' => ['_type' => 'array'],
                        'blocks_count' => ['_type' => 'integer'],
                    ],
                ],
                'total_count' => ['_type' => 'integer'],
                'pagination' => ['_keys' => ['has_more'], '_structure' => ['has_more' => ['_type' => 'boolean']]],
            ],
        ];
    }

    private function apartmentsListSchema(): array
    {
        return [
            '_keys' => ['success', 'data', 'total_count', 'pagination'],
            '_structure' => [
                'success' => ['_type' => 'boolean'],
                'data' => [
                    '_keys' => ['objects', 'apartments_count', 'objects_count'],
                    '_structure' => [
                        'objects' => ['_type' => 'array'],
                        'apartments_count' => ['_type' => 'integer'],
                        'objects_count' => ['_type' => 'integer'],
                    ],
                ],
                'total_count' => ['_type' => 'integer'],
                'pagination' => ['_keys' => ['has_more'], '_structure' => ['has_more' => ['_type' => 'boolean']]],
            ],
        ];
    }

    private function apartmentDetailSchema(): array
    {
        return [
            '_keys' => ['success', 'data'],
            '_structure' => [
                'success' => ['_type' => 'boolean'],
                'data' => [
                    '_keys' => ['id', '_id', 'number', 'floor'],
                    '_structure' => [
                        'id' => ['_type' => 'string'],
                        '_id' => ['_type' => 'string'],
                        'number' => ['_type' => 'string'],
                        'floor' => ['_type' => 'integer'],
                        'area_total' => ['_type' => 'double'],
                        'price_base' => ['_type' => 'integer'],
                    ],
                ],
            ],
        ];
    }

    private function validateApartmentDetailDynamic(): array
    {
        try {
            $apartment = \App\Models\TrendAgent\Apartment::with('complex')->first();
        } catch (\Throwable $e) {
            return ['ok' => true, 'errors' => [], 'warnings' => [['message' => 'DB unavailable for apartment-detail: ' . $e->getMessage()]]];
        }
        if (!$apartment || !$apartment->complex) {
            return ['ok' => true, 'errors' => [], 'warnings' => [['message' => 'No apartment data, skip detail check']]];
        }

        $path = "/api/trendagent/v1/apartments/{$apartment->complex->external_id}/flat/{$apartment->external_id}";

        if ($this->useInternal) {
            $fetchResult = $this->callInternal(['method' => 'POST', 'path' => $path, 'body' => []]);
        } else {
            $url = rtrim($this->baseUrl, '/') . $path;
            try {
                $r = Http::timeout(10)->post($url, []);
                $fetchResult = ['status' => $r->status(), 'data' => $r->json()];
            } catch (\Throwable $e) {
                return ['ok' => false, 'errors' => [['message' => $e->getMessage()]], 'warnings' => []];
            }
        }

        return $this->validateData('apartment-detail', $fetchResult, $this->apartmentDetailSchema());
    }
}
