<?php

return [
    // API Key Management
    'keys' => [
        'allow_internal' => env('ALLOW_INTERNAL_API_KEYS', true),
        'require_user_keys' => env('REQUIRE_USER_API_KEYS', false),
        'allow_in_request' => env('ALLOW_API_KEY_IN_REQUEST', true),
        'allow_user_storage' => env('ALLOW_USER_KEY_STORAGE', true),
    ],

    // Usage Limits
    'limits' => [
        'daily_requests' => env('DEFAULT_DAILY_REQUEST_LIMIT', 100),
        'monthly_tokens' => env('DEFAULT_MONTHLY_TOKEN_LIMIT', 100000),
        'max_file_size_mb' => env('DEFAULT_MAX_FILE_SIZE_MB', 10),
    ],

    // Features
    'features' => [
        'logging' => env('ENABLE_REQUEST_LOGGING', true),
        'analytics' => env('ENABLE_USAGE_ANALYTICS', true),
        'rate_limiting' => env('ENABLE_RATE_LIMITING', true),
        'cost_tracking' => env('ENABLE_COST_TRACKING', true),
    ],

    // Gemini Configuration
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'api_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'default_model' => 'gemini-1.5-pro',
        'models' => [
            'gemini-1.5-pro' => [
                'name' => 'Gemini 1.5 Pro',
                'input_price' => env('GEMINI_PRO_INPUT_PRICE', 0.50),
                'output_price' => env('GEMINI_PRO_OUTPUT_PRICE', 1.50),
                'supports_vision' => true,
                'max_tokens' => 1000000,
            ],
            'gemini-1.5-flash' => [
                'name' => 'Gemini 1.5 Flash',
                'input_price' => env('GEMINI_FLASH_INPUT_PRICE', 0.10),
                'output_price' => env('GEMINI_FLASH_OUTPUT_PRICE', 0.30),
                'supports_vision' => true,
                'max_tokens' => 1000000,
            ],
            'gemini-pro-vision' => [
                'name' => 'Gemini Pro Vision',
                'input_price' => 0.25,
                'output_price' => 0.50,
                'supports_vision' => true,
                'max_tokens' => 30720,
            ],
        ],
    ],

    // OpenAI Configuration
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'api_url' => 'https://api.openai.com/v1',
        'default_model' => 'gpt-4-turbo-preview',
        'models' => [
            'gpt-4-turbo-preview' => [
                'name' => 'GPT-4 Turbo',
                'input_price' => env('GPT4_TURBO_INPUT_PRICE', 10.00),
                'output_price' => env('GPT4_TURBO_OUTPUT_PRICE', 30.00),
                'supports_vision' => false,
                'max_tokens' => 128000,
            ],
            'gpt-4' => [
                'name' => 'GPT-4',
                'input_price' => 30.00,
                'output_price' => 60.00,
                'supports_vision' => false,
                'max_tokens' => 8192,
            ],
            'gpt-3.5-turbo' => [
                'name' => 'GPT-3.5 Turbo',
                'input_price' => env('GPT35_TURBO_INPUT_PRICE', 0.50),
                'output_price' => env('GPT35_TURBO_OUTPUT_PRICE', 1.50),
                'supports_vision' => false,
                'max_tokens' => 16385,
            ],
            'gpt-4-vision-preview' => [
                'name' => 'GPT-4 Vision',
                'input_price' => 10.00,
                'output_price' => 30.00,
                'supports_vision' => true,
                'max_tokens' => 128000,
            ],
        ],
    ],

    // Supported providers
    'providers' => ['gemini', 'openai'],
];
