<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TrendAgent Data Source
    |--------------------------------------------------------------------------
    */
    'data_source' => env('TRENDAGENT_DATA_SOURCE', 'remote'),

    /*
    |--------------------------------------------------------------------------
    | Parser credentials (для trendagent:parse — авторизация в trendagent.ru)
    |--------------------------------------------------------------------------
    | При config:cache env() не читает .env — значения должны быть в config.
    */
    'phone' => env('TRENDAGENT_PHONE', ''),
    'password' => env('TRENDAGENT_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Debug Query
    |--------------------------------------------------------------------------
    | При 1 — в response headers добавляются X-TrendAgent-Queries и X-TrendAgent-QueryTime
    */
    'debug_query' => (bool) env('TRENDAGENT_DEBUG_QUERY', false),
];
