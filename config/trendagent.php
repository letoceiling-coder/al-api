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
    | Debug Query
    |--------------------------------------------------------------------------
    | При 1 — в response headers добавляются X-TrendAgent-Queries и X-TrendAgent-QueryTime
    */
    'debug_query' => (bool) env('TRENDAGENT_DEBUG_QUERY', false),
];
