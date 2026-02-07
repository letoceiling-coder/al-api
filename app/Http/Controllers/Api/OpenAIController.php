<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OpenAIController extends Controller
{
    private OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Chat Completions
     * POST /api/openai/chat
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|string|in:system,user,assistant',
            'messages.*.content' => 'required|string',
            'model' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer',
        ]);

        $result = $this->openAIService->chatCompletion(
            $request->input('messages'),
            $request->input('model'),
            $request->only(['temperature', 'max_tokens', 'stream', 'functions', 'function_call'])
        );

        return response()->json($result);
    }

    /**
     * Vision - анализ изображений
     * POST /api/openai/vision
     */
    public function vision(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string',
            'images' => 'required|array|min:1',
            'images.*' => 'string',
            'model' => 'nullable|string',
        ]);

        $result = $this->openAIService->visionAnalysis(
            $request->input('prompt'),
            $request->input('images'),
            $request->input('model')
        );

        return response()->json($result);
    }

    /**
     * DALL-E - генерация изображений
     * POST /api/openai/image
     */
    public function image(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string',
            'size' => 'nullable|string|in:1024x1024,1792x1024,1024x1792',
            'model' => 'nullable|string',
            'n' => 'nullable|integer|min:1|max:10',
        ]);

        $result = $this->openAIService->generateImage(
            $request->input('prompt'),
            $request->input('size', '1024x1024'),
            $request->input('model'),
            $request->input('n', 1)
        );

        return response()->json($result);
    }

    /**
     * Whisper - транскрипция аудио
     * POST /api/openai/transcribe
     */
    public function transcribe(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,mp4,mpeg,mpga,m4a,wav,webm',
            'language' => 'nullable|string|size:2',
            'model' => 'nullable|string',
        ]);

        $audioPath = $request->file('audio')->getRealPath();
        
        $result = $this->openAIService->transcribeAudio(
            $audioPath,
            $request->input('language'),
            $request->input('model')
        );

        return response()->json($result);
    }

    /**
     * Embeddings
     * POST /api/openai/embedding
     */
    public function embedding(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string',
            'model' => 'nullable|string',
        ]);

        $result = $this->openAIService->createEmbedding(
            $request->input('text'),
            $request->input('model')
        );

        return response()->json($result);
    }

    /**
     * TTS - синтез речи
     * POST /api/openai/tts
     */
    public function tts(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:4096',
            'voice' => 'nullable|string|in:alloy,echo,fable,onyx,nova,shimmer',
            'model' => 'nullable|string|in:tts-1,tts-1-hd',
        ]);

        $result = $this->openAIService->textToSpeech(
            $request->input('text'),
            $request->input('voice', 'alloy'),
            $request->input('model')
        );

        return response()->json($result);
    }

    /**
     * Moderation
     * POST /api/openai/moderate
     */
    public function moderate(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string',
            'model' => 'nullable|string',
        ]);

        $result = $this->openAIService->moderateContent(
            $request->input('text'),
            $request->input('model')
        );

        return response()->json($result);
    }

    /**
     * Streaming chat
     * POST /api/openai/stream
     */
    public function stream(Request $request)
    {
        $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|string|in:system,user,assistant',
            'messages.*.content' => 'required|string',
            'model' => 'nullable|string',
        ]);

        return response()->stream(function () use ($request) {
            $this->openAIService->chatStream(
                $request->input('messages'),
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
     * Function calling
     * POST /api/openai/functions
     */
    public function functions(Request $request): JsonResponse
    {
        $request->validate([
            'messages' => 'required|array|min:1',
            'functions' => 'required|array',
            'model' => 'nullable|string',
        ]);

        $result = $this->openAIService->chatWithFunctions(
            $request->input('messages'),
            $request->input('functions'),
            $request->input('model')
        );

        return response()->json($result);
    }

    /**
     * JSON режим
     * POST /api/openai/json
     */
    public function json(Request $request): JsonResponse
    {
        $request->validate([
            'messages' => 'required|array|min:1',
            'model' => 'nullable|string',
        ]);

        $result = $this->openAIService->chatJson(
            $request->input('messages'),
            $request->input('model')
        );

        return response()->json($result);
    }
}
