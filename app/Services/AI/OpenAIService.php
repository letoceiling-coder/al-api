<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OpenAIService
{
    private string $apiKey;
    private string $apiUrl;
    private array $models;

    public function __construct()
    {
        $this->apiKey = config('ai.openai.api_key');
        $this->apiUrl = config('ai.openai.api_url');
        $this->models = config('ai.openai.models');
    }

    /**
     * Chat Completions (GPT-4, GPT-3.5)
     */
    public function chatCompletion(array $messages, string $model = null, array $options = []): array
    {
        $model = $model ?? $this->models['gpt4_turbo'];
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->apiUrl}/chat/completions", array_merge([
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
        ], $options));

        if ($response->successful()) {
            return [
                'success' => true,
                'content' => $response->json('choices.0.message.content', ''),
                'usage' => $response->json('usage', []),
                'full_response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'Unknown error'),
        ];
    }

    /**
     * Vision - анализ изображений
     */
    public function visionAnalysis(string $prompt, array $images, string $model = null): array
    {
        $model = $model ?? $this->models['gpt4_vision'];
        
        $messages = [
            [
                'role' => 'user',
                'content' => array_merge(
                    [['type' => 'text', 'text' => $prompt]],
                    array_map(function($image) {
                        if (filter_var($image, FILTER_VALIDATE_URL)) {
                            return [
                                'type' => 'image_url',
                                'image_url' => ['url' => $image]
                            ];
                        } else {
                            $imageData = base64_encode(file_get_contents($image));
                            $mimeType = mime_content_type($image);
                            return [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64,{$imageData}"
                                ]
                            ];
                        }
                    }, $images)
                )
            ]
        ];

        return $this->chatCompletion($messages, $model);
    }

    /**
     * DALL-E - генерация изображений
     */
    public function generateImage(string $prompt, string $size = '1024x1024', string $model = null, int $n = 1): array
    {
        $model = $model ?? $this->models['dalle'];
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->apiUrl}/images/generations", [
            'model' => $model,
            'prompt' => $prompt,
            'n' => $n,
            'size' => $size,
            'quality' => 'standard',
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'images' => array_map(function($item) {
                    return $item['url'] ?? $item['b64_json'] ?? null;
                }, $response->json('data', [])),
                'full_response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'Unknown error'),
        ];
    }

    /**
     * Whisper - транскрипция аудио
     */
    public function transcribeAudio(string $audioPath, string $language = null, string $model = null): array
    {
        $model = $model ?? $this->models['whisper'];
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
        ])->attach(
            'file',
            file_get_contents($audioPath),
            basename($audioPath)
        )->post("{$this->apiUrl}/audio/transcriptions", [
            'model' => $model,
            'language' => $language,
            'response_format' => 'json',
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'text' => $response->json('text', ''),
                'full_response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'Unknown error'),
        ];
    }

    /**
     * Text Embeddings
     */
    public function createEmbedding(string $text, string $model = null): array
    {
        $model = $model ?? $this->models['embedding_small'];
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->apiUrl}/embeddings", [
            'model' => $model,
            'input' => $text,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'embedding' => $response->json('data.0.embedding', []),
                'usage' => $response->json('usage', []),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'Unknown error'),
        ];
    }

    /**
     * TTS - синтез речи
     */
    public function textToSpeech(string $text, string $voice = 'alloy', string $model = null): array
    {
        $model = $model ?? $this->models['tts'];
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->apiUrl}/audio/speech", [
            'model' => $model,
            'input' => $text,
            'voice' => $voice,
            'response_format' => 'mp3',
        ]);

        if ($response->successful()) {
            $audioData = $response->body();
            $filename = 'tts_' . time() . '.mp3';
            Storage::put("audio/{$filename}", $audioData);
            
            return [
                'success' => true,
                'file' => $filename,
                'url' => Storage::url("audio/{$filename}"),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'Unknown error'),
        ];
    }

    /**
     * Moderation - модерация контента
     */
    public function moderateContent(string $text, string $model = null): array
    {
        $model = $model ?? $this->models['moderation'];
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->apiUrl}/moderations", [
            'model' => $model,
            'input' => $text,
        ]);

        if ($response->successful()) {
            $result = $response->json('results.0', []);
            return [
                'success' => true,
                'flagged' => $result['flagged'] ?? false,
                'categories' => $result['categories'] ?? [],
                'category_scores' => $result['category_scores'] ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'Unknown error'),
        ];
    }

    /**
     * Streaming chat
     */
    public function chatStream(array $messages, string $model = null, callable $callback = null): void
    {
        $model = $model ?? $this->models['gpt4_turbo'];
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->withOptions([
            'stream' => true,
        ])->post("{$this->apiUrl}/chat/completions", [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
        ]);

        if ($response->successful()) {
            foreach ($response->stream() as $chunk) {
                $data = json_decode($chunk, true);
                if (isset($data['choices'][0]['delta']['content'])) {
                    if ($callback) {
                        $callback($data['choices'][0]['delta']['content']);
                    }
                }
            }
        }
    }

    /**
     * Function calling
     */
    public function chatWithFunctions(array $messages, array $functions, string $model = null): array
    {
        return $this->chatCompletion($messages, $model, [
            'functions' => $functions,
            'function_call' => 'auto',
        ]);
    }

    /**
     * JSON режим
     */
    public function chatJson(array $messages, string $model = null): array
    {
        return $this->chatCompletion($messages, $model, [
            'response_format' => ['type' => 'json_object'],
        ]);
    }
}
