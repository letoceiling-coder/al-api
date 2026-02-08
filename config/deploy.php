<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TrendAgent deploy (SSH)
    |--------------------------------------------------------------------------
    | При запуске php artisan deploy:trendagent локально после git push
    | выполняется деплой на сервер по SSH.
    */

    'trendagent' => [
        'ssh'  => env('DEPLOY_SSH', 'root@89.169.39.244'),
        'path' => env('DEPLOY_PATH', '/var/www/AL'),
    ],

];
