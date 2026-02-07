<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GeminiController extends Controller
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Генерация текста
     * POST /api/gemini/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string',
            'model' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'maxOutputTokens' => 'nullable|integer',
        ]);

        $result = $this->geminiService->generateText(
            $request->input('prompt'),
            $request->input('model'),
            $request->only(['temperature', 'maxOutputTokens', 'topK', 'topP'])
        );

        return response()->json($result);
    }

    /**
     * Мультимодальный анализ
     * POST /api/gemini/multimodal
     */
    public function multimodal(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string',
            'images' => 'required|array|min:1',
            'images.*' => 'string',
            'model' => 'nullable|string',
        ]);

        $result = $this->geminiService->analyzeMultimodal(
            $request->input('prompt'),
            $request->input('images'),
            $request->input('model')
        );

        return response()->json($result);
    }

    /**
     * Streaming генерация
     * POST /api/gemini/stream
     */
    public function stream(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
            'model' => 'nullable|string',
        ]);

        return response()->stream(function () use ($request) {
            $this->geminiService->generateStream(
                $request->input('prompt'),
                $request->input('model'),
                function ($chunk) {
                    echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                    ob_flush();
                    flush();
                }
            );
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Создание embeddings
     * POST /api/gemini/embedding
     */
    public function embedding(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string',
        ]);

        $result = $this->geminiService->createEmbedding($request->input('text'));

        return response()->json($result);
    }

    /**
     * Function calling
     * POST /api/gemini/functions
     */
    public function functions(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string',
            'functions' => 'required|array',
            'model' => 'nullable|string',
        ]);

        $result = $this->geminiService->generateWithFunctions(
            $request->input('prompt'),
            $request->input('functions'),
            $request->input('model')
        );

        return response()->json($result);
    }

    /**
     * JSON режим
     * POST /api/gemini/json
     */
    public function json(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string',
            'model' => 'nullable|string',
        ]);

        $result = $this->geminiService->generateJson(
            $request->input('prompt'),
            $request->input('model')
        );

        return response()->json($result);
    }
}
