<?php

return [
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'api_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'default_model' => 'gemini-1.5-pro',
        'models' => [
            'pro' => 'gemini-1.5-pro',
            'pro_latest' => 'gemini-1.5-pro-latest',
            'flash' => 'gemini-1.5-flash',
            'pro_legacy' => 'gemini-pro',
            'vision' => 'gemini-pro-vision',
        ],
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'api_url' => 'https://api.openai.com/v1',
        'default_model' => 'gpt-4-turbo-preview',
        'models' => [
            'gpt4_turbo' => 'gpt-4-turbo-preview',
            'gpt4' => 'gpt-4',
            'gpt4_vision' => 'gpt-4-vision-preview',
            'gpt35_turbo' => 'gpt-3.5-turbo',
            'gpt35_turbo_16k' => 'gpt-3.5-turbo-16k',
            'dalle' => 'dall-e-3',
            'whisper' => 'whisper-1',
            'embedding_small' => 'text-embedding-3-small',
            'embedding_large' => 'text-embedding-3-large',
            'embedding_ada' => 'text-embedding-ada-002',
            'tts' => 'tts-1',
            'tts_hd' => 'tts-1-hd',
            'moderation' => 'text-moderation-latest',
        ],
    ],
];
