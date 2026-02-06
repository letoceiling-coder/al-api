<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $apiUrl;
    private array $models;

    public function __construct()
    {
        $this->apiKey = config('ai.gemini.api_key');
        $this->apiUrl = config('ai.gemini.api_url');
        $this->models = config('ai.gemini.models');
    }

    /**
     * Генерация текста
     */
    public function generateText(string $prompt, string $model = null, array $options = []): array
    {
        $model = $model ?? $this->models['pro'];
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$this->apiUrl}/models/{$model}:generateContent?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => array_merge([
                'temperature' => $options['temperature'] ?? 0.7,
                'topK' => $options['topK'] ?? 40,
                'topP' => $options['topP'] ?? 0.95,
                'maxOutputTokens' => $options['maxOutputTokens'] ?? 2048,
            ], $options),
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'text' => $response->json('candidates.0.content.parts.0.text', ''),
                'full_response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'Unknown error'),
        ];
    }

    /**
     * Мультимодальный анализ (текст + изображения)
     */
    public function analyzeMultimodal(string $prompt, array $images, string $model = null): array
    {
        $model = $model ?? $this->models['pro'];
        
        $parts = [['text' => $prompt]];
        
        foreach ($images as $image) {
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => 'image/jpeg',
                        'data' => base64_encode(file_get_contents($image))
                    ]
                ];
            } elseif (file_exists($image)) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => mime_content_type($image),
                        'data' => base64_encode(file_get_contents($image))
                    ]
                ];
            }
        }

        $response = Http::post("{$this->apiUrl}/models/{$model}:generateContent?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => $parts
                ]
            ],
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'text' => $response->json('candidates.0.content.parts.0.text', ''),
                'full_response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'Unknown error'),
        ];
    }

    /**
     * Streaming генерация
     */
    public function generateStream(string $prompt, string $model = null, callable $callback = null): void
    {
        $model = $model ?? $this->models['pro'];
        
        $response = Http::withOptions([
            'stream' => true,
        ])->post("{$this->apiUrl}/models/{$model}:streamGenerateContent?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
        ]);

        if ($response->successful()) {
            foreach ($response->stream() as $chunk) {
                $data = json_decode($chunk, true);
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    if ($callback) {
                        $callback($data['candidates'][0]['content']['parts'][0]['text']);
                    }
                }
            }
        }
    }

    /**
     * Создание embeddings
     */
    public function createEmbedding(string $text, string $model = 'models/embedding-001'): array
    {
        $response = Http::post("{$this->apiUrl}/models/{$model}:embedContent?key={$this->apiKey}", [
            'model' => $model,
            'content' => [
                'parts' => [
                    ['text' => $text]
                ]
            ],
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'embedding' => $response->json('embedding.values', []),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'Unknown error'),
        ];
    }

    /**
     * Function calling
     */
    public function generateWithFunctions(string $prompt, array $functions, string $model = null): array
    {
        $model = $model ?? $this->models['pro'];
        
        $response = Http::post("{$this->apiUrl}/models/{$model}:generateContent?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'tools' => [
                [
                    'function_declarations' => $functions
                ]
            ],
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'Unknown error'),
        ];
    }

    /**
     * JSON режим
     */
    public function generateJson(string $prompt, string $model = null): array
    {
        return $this->generateText($prompt, $model, [
            'responseMimeType' => 'application/json',
        ]);
    }
}
