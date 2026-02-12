<?php

namespace App\Services\TrendAgent;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Оптимизированная авторизация через SSO API
 * Использует прямой API запрос вместо браузерной автоматизации
 */
class TrendSsoApiAuth
{
    private Client $client;
    private CookieJar $cookieJar;
    private array $authData = [];
    private string $apiBaseUrl = 'https://sso-api.trend.tech';
    private string $ssoBaseUrl = 'https://sso.trend.tech';
    private string $appId = '66d84f584c0168b8ccd281c3';

    public function __construct()
    {
        $this->cookieJar = new CookieJar();
        $this->client = new Client([
            'cookies' => $this->cookieJar,
            'allow_redirects' => [
                'max' => 10,
                'strict' => false,
                'referer' => true,
                'protocols' => ['http', 'https'],
                'track_redirects' => true
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Origin' => 'https://sso.trend.tech',
                'Referer' => 'https://sso.trend.tech/',
            ],
            'verify' => false,
            'timeout' => 30,
        ]);
    }

    /**
     * Авторизация через SSO API
     *
     * @param string $phone Телефон в формате +7 999 637 11 82
     * @param string $password Пароль
     * @param string|null $appId ID приложения (по умолчанию из конструктора)
     * @return array Массив с данными авторизации
     * @throws \Exception
     */
    public function authenticate(string $phone, string $password, ?string $appId = null): array
    {
        try {
            Log::info('Начало авторизации через SSO API', [
                'api_url' => $this->apiBaseUrl,
            ]);

            // Форматируем телефон
            $phoneFormatted = $this->formatPhone($phone);

            // Шаг 1: Получаем начальную страницу /login для получения редиректа с app_id
            Log::info('Получение начальной страницы /login для извлечения app_id...');
            $appId = null;
            try {
                // Включаем автоматические редиректы и отслеживаем историю
                $response = $this->client->get($this->ssoBaseUrl . '/login', [
                    'cookies' => $this->cookieJar,
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => false,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                        'track_redirects' => true, // Включаем отслеживание редиректов
                    ],
                    'headers' => [
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                        'Accept-Encoding' => 'gzip, deflate, br',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                        'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                        'Sec-Ch-Ua-Mobile' => '?0',
                        'Sec-Ch-Ua-Platform' => '"Windows"',
                        'Sec-Fetch-Dest' => 'document',
                        'Sec-Fetch-Mode' => 'navigate',
                        'Sec-Fetch-Site' => 'none',
                        'Sec-Fetch-User' => '?1',
                        'Upgrade-Insecure-Requests' => '1',
                    ],
                ]);
                
                $statusCode = $response->getStatusCode();
                Log::info('Ответ от /login', ['status_code' => $statusCode]);
                
                // Получаем историю редиректов
                $redirectHistory = $response->getHeader('X-Guzzle-Redirect-History');
                $redirectCount = $response->getHeader('X-Guzzle-Redirect-Status-History');
                
                Log::info('История редиректов', [
                    'redirect_count' => count($redirectHistory ?? []),
                    'redirect_urls' => $redirectHistory ?? [],
                    'redirect_statuses' => $redirectCount ?? [],
                ]);
                
                // Берем последний URL из истории редиректов (финальный URL)
                $finalUrl = null;
                if (!empty($redirectHistory)) {
                    $finalUrl = end($redirectHistory);
                } else {
                    // Если редиректов не было, используем исходный URL
                    $finalUrl = $this->ssoBaseUrl . '/login';
                }
                
                Log::info('Финальный URL после редиректов', ['url' => $finalUrl]);
                
                // Извлекаем app_id из финального URL
                $parsedUrl = parse_url($finalUrl);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    if (isset($queryParams['app_id'])) {
                        $appId = $queryParams['app_id'];
                        Log::info('app_id извлечен из финального URL', ['app_id' => $appId]);
                    }
                }
                
                // Если редиректов не было, но страница загрузилась (200), 
                // значит редирект происходит через JavaScript
                // В этом случае app_id должен быть в URL после выполнения JavaScript
                // Но так как мы не выполняем JS, пробуем извлечь из HTML
                
                // Если app_id не найден, пробуем извлечь из тела ответа
                if (empty($appId)) {
                    $body = $response->getBody()->getContents();
                    $response->getBody()->rewind();
                    
                    Log::info('Поиск app_id в теле ответа', ['body_length' => strlen($body)]);
                    
                    // Пробуем найти app_id в различных форматах:
                    // 1. В URL: ?app_id=... или /recover?app_id=...
                    // 2. В скриптах: app_id: "..."
                    // 3. В атрибутах: data-app-id="..."
                    // 4. В переменных JavaScript: var appId = "..."
                    // 5. В ссылках: href="/recover?app_id=..."
                    $patterns = [
                        '/[?&]app_id[=:]\s*([a-f0-9]{24})/i',  // В URL параметрах (приоритет)
                        '/href=["\'][^"\']*app_id[=:]([a-f0-9]{24})/i',  // В ссылках (приоритет)
                        '/app_id[=:]\s*["\']?([a-f0-9]{24})["\']?/i',
                        '/appId[=:]\s*["\']?([a-f0-9]{24})["\']?/i',
                        '/app-id[=:]\s*["\']?([a-f0-9]{24})["\']?/i',
                        '/["\']app_id["\']\s*:\s*["\']([a-f0-9]{24})["\']/i',
                    ];
                    
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $body, $matches)) {
                            $appId = $matches[1];
                            Log::info('app_id найден в теле ответа', ['app_id' => $appId, 'pattern' => $pattern]);
                            break;
                        }
                    }
                    
                    // Если не нашли, ищем любые упоминания app_id в HTML
                    if (empty($appId)) {
                        // Ищем все упоминания app_id (может быть разной длины)
                        if (preg_match_all('/app_id[=:]([a-f0-9]{20,30})/i', $body, $allMatches)) {
                            Log::info('Найдены возможные app_id в HTML', [
                                'matches' => $allMatches[1],
                                'count' => count($allMatches[1]),
                            ]);
                            // Берем первый найденный (обычно это правильный)
                            if (!empty($allMatches[1][0])) {
                                $appId = $allMatches[1][0];
                                Log::info('Используется первый найденный app_id', ['app_id' => $appId]);
                            }
                        }
                        
                        if (empty($appId)) {
                            // Сохраняем больше HTML для отладки
                            $bodySample = substr($body, 0, 5000);
                            Log::warning('app_id не найден в теле ответа', [
                                'body_sample' => $bodySample,
                                'body_length' => strlen($body),
                            ]);
                        }
                    }
                }
                
                // Если app_id не найден, это SPA и app_id генерируется через JavaScript
                // В этом случае пробуем использовать app_id из исходного URL авторизации
                // или генерируем новый запрос, который вернет app_id в редиректе
                if (empty($appId)) {
                    Log::info('app_id не найден в HTML (SPA), пробуем получить через редирект...');
                    
                    // Пробуем сделать запрос, который должен вернуть редирект с app_id
                    // Или используем app_id из исходного URL, если он был передан
                    try {
                        // Пробуем запросить страницу с параметрами, которые могут вызвать редирект
                        $testResponse = $this->client->get($this->ssoBaseUrl . '/login', [
                            'cookies' => $this->cookieJar,
                            'allow_redirects' => [
                                'max' => 3,
                                'track_redirects' => true,
                            ],
                            'headers' => [
                                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                            ],
                        ]);
                        
                        // Проверяем историю редиректов еще раз
                        $testRedirectHistory = $testResponse->getHeader('X-Guzzle-Redirect-History');
                        if (!empty($testRedirectHistory)) {
                            foreach ($testRedirectHistory as $redirectUrl) {
                                $parsed = parse_url($redirectUrl);
                                if (isset($parsed['query'])) {
                                    parse_str($parsed['query'], $params);
                                    if (isset($params['app_id'])) {
                                        $appId = $params['app_id'];
                                        Log::info('app_id найден в редиректе при повторном запросе', ['app_id' => $appId]);
                                        break;
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Ошибка при попытке получить app_id через редирект', ['error' => $e->getMessage()]);
                    }
                    
                    // Если все еще не найден, используем значение по умолчанию
                    // Но это может не сработать, так как app_id может быть динамическим
                    if (empty($appId)) {
                        $appId = $this->appId;
                        Log::warning('app_id не найден, используется значение по умолчанию. Возможно, нужна браузерная автоматизация для получения динамического app_id', ['app_id' => $appId]);
                    }
                }
                
                // Если app_id все еще не найден, используем значение по умолчанию
                if (empty($appId)) {
                    $appId = $this->appId;
                    Log::warning('app_id не найден в редиректе, используется значение по умолчанию', ['app_id' => $appId]);
                } else {
                    Log::info('Используется app_id из редиректа', ['app_id' => $appId]);
                }
                
                // ВАЖНО: Загружаем страницу с app_id в URL для получения cookies
                // Это может быть необходимо для установки session cookies
                // Также пробуем несколько вариантов app_id, так как он может быть динамическим
                Log::info('Загрузка страницы с app_id для получения cookies...');
                
                // Пробуем разные варианты app_id (из реального запроса и дефолтный)
                // ВАЖНО: В реальном успешном запросе app_id = 66d84ffc4c0168b8ccd281c7
                $appIdVariants = [
                    '66d84ffc4c0168b8ccd281c7', // Из реального успешного запроса (ПРИОРИТЕТ)
                    $appId, // Текущий app_id
                    $this->appId, // Дефолтный
                ];
                $appIdVariants = array_unique($appIdVariants);
                
                $bestAppId = $appId;
                $maxCookies = 0;
                
                foreach ($appIdVariants as $variantAppId) {
                    try {
                        $responseWithAppId = $this->client->get($this->ssoBaseUrl . '/login?app_id=' . urlencode($variantAppId), [
                            'cookies' => $this->cookieJar,
                            'allow_redirects' => true,
                            'headers' => [
                                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                                'Accept-Encoding' => 'gzip, deflate, br',
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                                'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                                'Sec-Ch-Ua-Mobile' => '?0',
                                'Sec-Ch-Ua-Platform' => '"Windows"',
                                'Sec-Fetch-Dest' => 'document',
                                'Sec-Fetch-Mode' => 'navigate',
                                'Sec-Fetch-Site' => 'none',
                                'Sec-Fetch-User' => '?1',
                                'Upgrade-Insecure-Requests' => '1',
                            ],
                        ]);
                        
                        $cookiesAfterAppIdLoad = $this->getCookies();
                        Log::info('Страница с app_id загружена', [
                            'app_id_variant' => $variantAppId,
                            'cookies_count' => count($cookiesAfterAppIdLoad),
                            'cookie_names' => array_keys($cookiesAfterAppIdLoad),
                            'status_code' => $responseWithAppId->getStatusCode(),
                        ]);
                        
                        // Выбираем app_id, который дал больше всего cookies
                        $cookiesCount = count($cookiesAfterAppIdLoad);
                        if ($cookiesCount > $maxCookies) {
                            $maxCookies = $cookiesCount;
                            $bestAppId = $variantAppId;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Ошибка при загрузке страницы с app_id', [
                            'app_id_variant' => $variantAppId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // Используем лучший app_id (который дал больше всего cookies)
                if (!empty($bestAppId) && $bestAppId !== $appId) {
                    $appId = $bestAppId;
                    Log::info('Выбран app_id, который дал больше всего cookies', [
                        'new_app_id' => $appId,
                        'cookies_count' => $maxCookies,
                    ]);
                }
                
                Log::info('Начальная страница получена', [
                    'cookies_count' => count($this->getCookies()),
                    'app_id' => $appId,
                ]);
            } catch (\Exception $e) {
                Log::warning('Ошибка при получении начальной страницы', [
                    'error' => $e->getMessage(),
                ]);
                // Используем app_id по умолчанию или переданный параметр
                $appId = $appId ?? $this->appId;
            }

            // Шаг 2: Выполняем авторизацию через API
            // ВАЖНО: Если cookies не получены, пробуем использовать app_id из реального успешного запроса
            // Это может помочь обойти защиту от ботов
            if (count($this->getCookies()) === 0) {
                Log::warning('Cookies не получены, пробуем использовать app_id из реального успешного запроса', [
                    'app_id_from_request' => '66d84ffc4c0168b8ccd281c7',
                    'current_app_id' => $appId,
                ]);
                // Используем app_id из реального успешного запроса
                $appId = '66d84ffc4c0168b8ccd281c7';
            }
            
            Log::info('Отправка запроса авторизации через API...', [
                'app_id' => $appId,
                'phone' => substr($phoneFormatted, 0, 5) . '***',
            ]);
            
            $loginUrl = $this->apiBaseUrl . '/v1/login?app_id=' . urlencode($appId) . '&lang=ru';
            
            // Логируем данные, которые будут отправлены (без пароля)
            Log::info('Данные для отправки', [
                'url' => $loginUrl,
                'phone' => substr($phoneFormatted, 0, 5) . '***',
                'client' => 'web',
                'cookies_count' => count($this->getCookies()),
            ]);
            
            // Отключаем автоматические редиректы для POST, чтобы получить cookies и ответ
            $response = $this->client->post($loginUrl, [
                'cookies' => $this->cookieJar,
                'allow_redirects' => false, // НЕ следуем редиректам автоматически, чтобы получить cookies
                'form_params' => [
                    'phone' => $phoneFormatted,
                    'password' => $password,
                    'client' => 'web',
                ],
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Origin' => 'https://sso.trend.tech',
                    'Referer' => $this->ssoBaseUrl . '/login?app_id=' . urlencode($appId), // Используем URL с app_id
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            
            // Сохраняем полный ответ для анализа
            $responseHeaders = $response->getHeaders();
            
            // Логируем полный ответ для анализа
            Log::info('Ответ от API авторизации', [
                'status_code' => $statusCode,
                'content_type' => $responseHeaders['Content-Type'][0] ?? null,
                'location' => $responseHeaders['Location'][0] ?? null,
                'set_cookie_count' => count($responseHeaders['Set-Cookie'] ?? []),
                'body_preview' => substr($body, 0, 1000),
                'body_length' => strlen($body),
            ]);
            
            // Проверяем cookies после запроса
            $cookiesAfterRequest = $this->getCookies();
            Log::info('Cookies после POST запроса', [
                'cookies_count' => count($cookiesAfterRequest),
                'cookie_names' => array_keys($cookiesAfterRequest),
            ]);
            
            // Ищем auth_token в cookies
            $authTokenFromCookie = null;
            foreach ($cookiesAfterRequest as $name => $cookie) {
                if ($name === 'auth_token' || stripos($name, 'auth') !== false) {
                    $authTokenFromCookie = $cookie['value'];
                    Log::info('Найден auth_token в cookie', ['cookie_name' => $name]);
                    break;
                }
            }

            // Обрабатываем редирект (302/301) как успешную авторизацию
            if ($statusCode >= 300 && $statusCode < 400) {
                $location = $responseHeaders['Location'][0] ?? null;
                Log::info('Получен редирект после авторизации', [
                    'status_code' => $statusCode,
                    'location' => $location,
                    'has_auth_token_cookie' => !empty($authTokenFromCookie),
                ]);
                
                // Если есть auth_token в cookies, считаем авторизацию успешной
                if (!empty($authTokenFromCookie)) {
                    Log::info('Авторизация успешна (токен в cookies)');
                    // Продолжаем обработку с токеном из cookies
                } else {
                    // Пробуем извлечь токен из Location
                    if (!empty($location)) {
                        $parsedLocation = parse_url($location);
                        if (isset($parsedLocation['query'])) {
                            parse_str($parsedLocation['query'], $locationParams);
                            if (isset($locationParams['auth_token'])) {
                                $authTokenFromCookie = $locationParams['auth_token'];
                                Log::info('Токен найден в Location редиректа');
                            }
                        }
                    }
                }
            } elseif ($statusCode === 201) {
                // 201 Created - успешная авторизация (как показал пользователь)
                Log::info('Получен 201 Created - авторизация успешна');
            } elseif ($statusCode === 403) {
                // 403 Forbidden - возможно, нужны дополнительные заголовки или cookies
                Log::error('Получен 403 Forbidden', [
                    'response_body' => $body,
                    'cookies_before' => count($this->getCookies()),
                    'set_cookie_headers' => $responseHeaders['Set-Cookie'] ?? [],
                ]);
                
                // Пробуем извлечь токен из cookies, даже если получили 403
                // (возможно, сервер все равно установил cookies)
                if (!empty($authTokenFromCookie)) {
                    Log::info('Несмотря на 403, токен найден в cookies - продолжаем');
                } else {
                    throw new \Exception("API вернул 403 Forbidden. Возможно, требуется дополнительная авторизация или защита от ботов. Ответ: " . substr($body, 0, 200));
                }
            } elseif ($statusCode !== 200) {
                // Если это не редирект, не 200 и не 201, это ошибка
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            // Парсим JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Если не JSON, возможно это редирект или другой формат
                Log::warning('Ответ не является JSON', [
                    'content_type' => $responseHeaders['Content-Type'][0] ?? null,
                    'body_preview' => substr($body, 0, 200),
                    'json_error' => json_last_error_msg(),
                ]);
                
                // Проверяем, не является ли это редиректом
                $location = $responseHeaders['Location'][0] ?? null;
                if ($location) {
                    Log::info('Обнаружен редирект в ответе', ['location' => $location]);
                    // Продолжаем обработку редиректа
                    $data = []; // Пустой массив для продолжения
                } else {
                    throw new \Exception('Ошибка парсинга JSON ответа: ' . json_last_error_msg() . '. Тело ответа: ' . substr($body, 0, 200));
                }
            } else {
                // Логируем структуру JSON ответа
                Log::info('Структура JSON ответа', [
                    'keys' => array_keys($data),
                    'has_token' => isset($data['token']),
                    'has_access_token' => isset($data['access_token']),
                    'has_auth_token' => isset($data['auth_token']),
                    'has_user' => isset($data['user']),
                    'has_error' => isset($data['error']) || isset($data['errors']),
                    'has_data' => isset($data['data']),
                ]);
            }

            // Проверяем наличие ошибок
            if (isset($data['error']) || isset($data['errors'])) {
                $errorMessage = $data['error'] ?? (is_array($data['errors']) ? implode(', ', $data['errors']) : 'Неизвестная ошибка');
                Log::error('Ошибка авторизации в ответе API', [
                    'error' => $errorMessage,
                    'full_response' => $data,
                ]);
                throw new \Exception('Ошибка авторизации: ' . $errorMessage);
            }

            // Извлекаем токен из ответа (пробуем все возможные варианты)
            $tokens = [];
            
            // ПРИОРИТЕТ 1: Токен из JSON ответа (как показал пользователь - токены в JSON)
            if (!empty($data)) {
                // Вариант 1: Поле auth_token (как в реальном ответе)
                if (isset($data['auth_token'])) {
                    $tokens['access_token'] = $data['auth_token'];
                    $tokens['auth_token'] = $data['auth_token'];
                    Log::info('Токен найден в поле "auth_token"');
                }
                
                // Вариант 2: Поле refresh_token (тоже сохраняем)
                if (isset($data['refresh_token'])) {
                    $tokens['refresh_token'] = $data['refresh_token'];
                    Log::info('Refresh token найден');
                }
                
                // Вариант 3: Прямое поле token
                if (isset($data['token']) && empty($tokens['auth_token'])) {
                    $tokens['access_token'] = $data['token'];
                    $tokens['auth_token'] = $data['token'];
                    Log::info('Токен найден в поле "token"');
                }
                
                // Вариант 4: Поле access_token
                if (isset($data['access_token']) && empty($tokens['auth_token'])) {
                    $tokens['access_token'] = $data['access_token'];
                    $tokens['auth_token'] = $data['access_token'];
                    Log::info('Токен найден в поле "access_token"');
                }
                
                // Вариант 5: Вложенное поле data.auth_token
                if (isset($data['data']['auth_token']) && empty($tokens['auth_token'])) {
                    $tokens['access_token'] = $data['data']['auth_token'];
                    $tokens['auth_token'] = $data['data']['auth_token'];
                    Log::info('Токен найден в поле "data.auth_token"');
                }
                
                // Вариант 6: Вложенное поле data.token
                if (isset($data['data']['token']) && empty($tokens['auth_token'])) {
                    $tokens['access_token'] = $data['data']['token'];
                    $tokens['auth_token'] = $data['data']['token'];
                    Log::info('Токен найден в поле "data.token"');
                }
            }
            
            // ПРИОРИТЕТ 2: Токен из cookies (fallback)
            if (empty($tokens['auth_token']) && !empty($authTokenFromCookie)) {
                $tokens['access_token'] = $authTokenFromCookie;
                $tokens['auth_token'] = $authTokenFromCookie;
                Log::info('Токен найден в cookies (fallback)', [
                    'token_preview' => substr($authTokenFromCookie, 0, 50) . '...',
                ]);
            }

            // Проверяем редирект в заголовках
            $location = $response->getHeaderLine('Location');
            if (!empty($location)) {
                Log::info('Обнаружен редирект в заголовках', ['location' => $location]);
                
                // Пробуем извлечь токен из URL редиректа
                $parsedUrl = parse_url($location);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    Log::info('Параметры редиректа', ['params' => array_keys($queryParams)]);
                    
                    if (isset($queryParams['auth_token']) && empty($tokens['auth_token'])) {
                        $tokens['access_token'] = $queryParams['auth_token'];
                        $tokens['auth_token'] = $queryParams['auth_token'];
                        Log::info('Токен найден в параметре редиректа "auth_token"');
                    }
                    if (isset($queryParams['access_token']) && empty($tokens['access_token'])) {
                        $tokens['access_token'] = $queryParams['access_token'];
                        $tokens['auth_token'] = $queryParams['access_token'];
                        Log::info('Токен найден в параметре редиректа "access_token"');
                    }
                }
                
                // Проверяем fragment (#)
                if (isset($parsedUrl['fragment'])) {
                    parse_str($parsedUrl['fragment'], $fragmentParams);
                    if (isset($fragmentParams['auth_token']) && empty($tokens['auth_token'])) {
                        $tokens['access_token'] = $fragmentParams['auth_token'];
                        $tokens['auth_token'] = $fragmentParams['auth_token'];
                        Log::info('Токен найден в fragment редиректа');
                    }
                }
            }
            
            // Проверяем токен в заголовках ответа
            $authTokenHeader = $response->getHeaderLine('X-Auth-Token');
            if (!empty($authTokenHeader) && empty($tokens['auth_token'])) {
                $tokens['access_token'] = $authTokenHeader;
                $tokens['auth_token'] = $authTokenHeader;
                Log::info('Токен найден в заголовке X-Auth-Token');
            }

            // Получаем cookies
            $cookies = $this->getCookies();

            // Извлекаем данные пользователя из ответа (если есть)
            $userData = null;
            if (isset($data['user']) && is_array($data['user'])) {
                $userData = $data['user'];
                Log::info('Данные пользователя получены', [
                    'user_id' => $userData['id'] ?? null,
                    'user_name' => $userData['name'] ?? null,
                ]);
            }

            // Сохраняем данные авторизации
            $this->authData = [
                'cookies' => $cookies,
                'headers' => $this->getAuthHeaders(),
                'tokens' => $tokens,
                'session_id' => $this->getSessionId(),
                'current_url' => $location ?? null,
                'authenticated' => !empty($tokens['auth_token']), // Авторизован, если есть токен
                'timestamp' => now()->toIso8601String(),
                'api_response' => $data, // Сохраняем полный ответ API для отладки
                'user' => $userData, // Данные пользователя
                'status_code' => $statusCode, // Статус код ответа
            ];

            // Если токен не найден, пробуем извлечь из cookies
            if (empty($tokens['auth_token'])) {
                foreach ($cookies as $name => $cookie) {
                    if (stripos($name, 'token') !== false || stripos($name, 'auth') !== false) {
                        $this->authData['tokens']['access_token'] = $cookie['value'];
                        $this->authData['tokens']['auth_token'] = $cookie['value'];
                        Log::info('Токен найден в cookie', ['cookie_name' => $name]);
                        break;
                    }
                }
            }

            Log::info('Авторизация через API успешна', [
                'has_cookies' => !empty($cookies),
                'has_tokens' => !empty($this->authData['tokens']),
                'tokens_keys' => array_keys($this->authData['tokens']),
            ]);

            return $this->authData;

        } catch (GuzzleException $e) {
            $response = method_exists($e, 'hasResponse') && $e->hasResponse() ? $e->getResponse() : null;
            $responseBody = $response ? $response->getBody()->getContents() : 'N/A';
            $statusCode = $response ? $response->getStatusCode() : 'N/A';
            
            Log::error('Ошибка Guzzle при авторизации через API', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'status_code' => $statusCode,
                'response_body' => substr($responseBody, 0, 500),
            ]);
            throw new \Exception('Ошибка при авторизации через API: ' . $e->getMessage() . (($statusCode !== 'N/A') ? " (HTTP {$statusCode})" : ''));
        } catch (\Exception $e) {
            Log::error('Общая ошибка при авторизации через API', [
                'message' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 1000),
            ]);
            throw $e;
        }
    }

    /**
     * Форматирование телефона
     */
    private function formatPhone(string $phone): string
    {
        // Убираем все пробелы и символы, оставляем только цифры и +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Если начинается с +7, оставляем как есть
        if (strpos($phone, '+7') === 0) {
            return $phone;
        }
        
        // Если начинается с 7, добавляем +
        if (strpos($phone, '7') === 0) {
            return '+' . $phone;
        }
        
        // Если начинается с 8, заменяем на +7
        if (strpos($phone, '8') === 0) {
            return '+7' . substr($phone, 1);
        }
        
        return $phone;
    }

    /**
     * Получение всех cookies в виде массива
     */
    public function getCookies(): array
    {
        $cookies = [];
        foreach ($this->cookieJar as $cookie) {
            $cookies[$cookie->getName()] = [
                'value' => $cookie->getValue(),
                'domain' => $cookie->getDomain(),
                'path' => $cookie->getPath(),
                'expires' => $cookie->getExpires(),
            ];
        }
        return $cookies;
    }

    /**
     * Получение заголовков для авторизованных запросов
     */
    public function getAuthHeaders(): array
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        ];

        $tokens = $this->authData['tokens'] ?? [];
        if (isset($tokens['access_token'])) {
            $headers['Authorization'] = 'Bearer ' . $tokens['access_token'];
        }

        return $headers;
    }

    /**
     * Получение Session ID из cookies
     */
    private function getSessionId(): ?string
    {
        $cookies = $this->getCookies();
        
        foreach ($cookies as $name => $cookie) {
            if (stripos($name, 'session') !== false) {
                return $cookie['value'];
            }
        }

        return null;
    }

    /**
     * Получение всех данных авторизации
     */
    public function getAuthData(): array
    {
        return $this->authData;
    }

    /**
     * Проверка, авторизован ли пользователь
     */
    public function isAuthenticated(): bool
    {
        return !empty($this->authData) && ($this->authData['authenticated'] ?? false);
    }

    /**
     * Получение токена для API запросов
     */
    public function getAuthToken(): ?string
    {
        $tokens = $this->authData['tokens'] ?? [];
        return $tokens['auth_token'] ?? $tokens['access_token'] ?? null;
    }

    /**
     * Получение контента защищенной страницы
     *
     * @param string $url URL страницы для получения
     * @param array $options Дополнительные опции для запроса
     * @return array Массив с контентом и метаданными
     * @throws \Exception
     */
    public function getPageContent(string $url, array $options = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            Log::info('Получение контента страницы', ['url' => $url]);

            // Получаем токен авторизации
            $authToken = $this->getAuthToken();
            
            // Формируем заголовки с cookies
            $headers = array_merge($this->getAuthHeaders(), [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer' => 'https://spb.trendagent.ru/',
                'Origin' => 'https://spb.trendagent.ru',
                'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                'Sec-Ch-Ua-Mobile' => '?0',
                'Sec-Ch-Ua-Platform' => '"Windows"',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'same-origin',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
            ]);

            // Если есть токен, добавляем его в заголовок Authorization или в URL
            if (!empty($authToken)) {
                // Пробуем добавить токен в URL, если это необходимо
                $parsedUrl = parse_url($url);
                if (!isset($parsedUrl['query']) || strpos($parsedUrl['query'], 'auth_token') === false) {
                    $separator = isset($parsedUrl['query']) ? '&' : '?';
                    $url = $url . $separator . 'auth_token=' . urlencode($authToken);
                }
            }

            $requestOptions = array_merge([
                'cookies' => $this->cookieJar,
                'headers' => $headers,
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'referer' => true,
                ],
                'timeout' => 30,
                'verify' => false,
            ], $options);

            $response = $this->client->get($url, $requestOptions);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $responseHeaders = $response->getHeaders();

            Log::info('Контент страницы получен', [
                'url' => $url,
                'status_code' => $statusCode,
                'content_length' => strlen($body),
            ]);

            if ($statusCode >= 400) {
                throw new \Exception("Ошибка при получении страницы: HTTP {$statusCode}");
            }

            return [
                'url' => $url,
                'status_code' => $statusCode,
                'content' => $body,
                'headers' => $responseHeaders,
                'content_length' => strlen($body),
                'content_type' => $responseHeaders['Content-Type'][0] ?? 'unknown',
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка при получении контента страницы', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
            throw new \Exception('Ошибка при получении контента: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Генерация ключа кэша на основе метода и параметров
     * 
     * @param string $method Название метода
     * @param array $params Параметры запроса
     * @return string Ключ кэша
     */
    private function getCacheKey(string $method, array $params = []): string
    {
        // Исключаем auth_token из ключа кэша, так как он может меняться
        $cacheParams = $params;
        unset($cacheParams['auth_token']);
        
        // Сортируем параметры для консистентности ключа
        ksort($cacheParams);
        
        // Генерируем хэш из параметров
        $paramsHash = md5(json_encode($cacheParams));
        
        return "trendagent:{$method}:" . $paramsHash;
    }

    /**
     * Получение данных объектов через API blocks/search
     * 
     * @param array $params Параметры запроса (show_type, sort, sort_order, count, city, lang и т.д.)
     * @return array Массив с данными объектов
     * @throws \Exception
     */
    public function getBlocksSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        // Генерируем ключ кэша
        $cacheKey = $this->getCacheKey('blocks_search', $params);
        
        // Пытаемся получить данные из кэша (60 минут)
        // Используем try-catch для обработки ошибок кеша (если БД недоступна)
        try {
            return Cache::remember($cacheKey, 60 * 60, function () use ($params) {
            try {
                // Получаем токен авторизации
                $authToken = $this->getAuthToken();

                if (empty($authToken)) {
                    throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
                }

                // Формируем URL API
                $apiUrl = 'https://api.trendagent.ru/v4_29/blocks/search/';
                
                // Параметры по умолчанию
                $defaultParams = [
                    'show_type' => 'list',
                    'sort' => 'price',
                    'sort_order' => 'asc',
                    'count' => 20,
                    'offset' => 0, // Смещение для пагинации
                    'city' => '58c665588b6aa52311afa01b', // Санкт-Петербург
                    'lang' => 'ru',
                ];

                // Объединяем параметры
                $queryParams = array_merge($defaultParams, $params);
                $queryParams['auth_token'] = $authToken;

            // Обрабатываем параметр room для правильного формирования URL
            // room может быть массивом [30, 40] и должен стать room=30&room=40
            // room=30 - Коттеджи (дома с участками)
            // room=40 - Таунхаусы (дома с участками)
            $roomParams = [];
            if (isset($queryParams['room']) && is_array($queryParams['room'])) {
                $roomParams = $queryParams['room'];
                unset($queryParams['room']); // Удаляем из основных параметров
            } elseif (isset($queryParams['room'])) {
                // Если одно значение, делаем массив
                $roomParams = [$queryParams['room']];
                unset($queryParams['room']);
            }
            
            // Формируем базовый query string
            $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
            
            // Добавляем параметры room отдельно (room=30&room=40)
            if (!empty($roomParams)) {
                $roomQuery = [];
                foreach ($roomParams as $room) {
                    $roomQuery[] = 'room=' . urlencode($room);
                }
                if (!empty($queryString)) {
                    $queryString .= '&' . implode('&', $roomQuery);
                } else {
                    $queryString = implode('&', $roomQuery);
                }
            }
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . $queryString;

            Log::info('Запрос к API blocks/search', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
                'params' => array_merge($queryParams, ['auth_token' => '***']), // Не логируем токен
            ]);

            // Выполняем запрос к API с правильными заголовками
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br, zstd',
                    'Origin' => 'https://spb.trendagent.ru',
                    'Referer' => 'https://spb.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            // 304 Not Modified - данные не изменились, используем кэш
            if ($statusCode === 304) {
                Log::info('API вернул 304 Not Modified - данные не изменились');
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('API вернул ошибку', [
                    'status_code' => $statusCode,
                    'body_preview' => substr($body, 0, 500),
                ]);
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON ответа: ' . json_last_error_msg());
            }

            if (isset($data['errors']) && !empty($data['errors'])) {
                throw new \Exception('API вернул ошибки: ' . json_encode($data['errors']));
            }

            Log::info('Данные получены через API blocks/search', [
                'results_count' => count($data['data']['results'] ?? []),
                'blocks_count' => $data['data']['blocksCount'] ?? 0,
                'prelaunches_count' => $data['data']['prelaunchesCount'] ?? 0,
            ]);

            // Обрабатываем результаты: добавляем полные URL для изображений
            $results = $data['data']['results'] ?? [];
            $blocksCount = $data['data']['blocksCount'] ?? 0; // Сохраняем blocksCount из API
            $processedResults = array_map(function($item) {
                // Обрабатываем изображение, если оно есть
                if (isset($item['image']) && is_array($item['image'])) {
                    $image = $item['image'];
                    if (isset($image['path']) && isset($image['file_name'])) {
                        // Формируем полный URL изображения
                        // Формат: https://selcdn.trendagent.ru/images/{path}m_{file_name}
                        $path = rtrim($image['path'], '/');
                        $fileName = $image['file_name'];
                        
                        // Убираем слэш в начале path, если есть
                        $path = ltrim($path, '/');
                        
                        // Формируем URL с префиксом "m_" для миниатюры
                        $item['image']['url'] = "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}";
                        // Полный размер изображения (без префикса "m_")
                        $item['image']['url_full'] = "https://selcdn.trendagent.ru/images/{$path}/{$fileName}";
                        
                        Log::debug('Сформирован URL изображения', [
                            'path' => $path,
                            'file_name' => $fileName,
                            'url' => $item['image']['url'],
                            'url_full' => $item['image']['url_full'],
                        ]);
                    } else {
                        Log::debug('Изображение без path или file_name', [
                            'image_structure' => $image,
                        ]);
                    }
                }
                return $item;
            }, $results);
            
            // Логируем структуру первого объекта для отладки
            if (!empty($processedResults)) {
                $firstObject = $processedResults[0];
                Log::info('Структура первого объекта из API', [
                    'has_image' => isset($firstObject['image']),
                    'image_structure' => $firstObject['image'] ?? null,
                    'object_keys' => array_keys($firstObject),
                ]);
            }

                $blocksCount = $data['data']['blocksCount'] ?? 0;
                return [
                    'success' => true,
                    'data' => $processedResults,
                    'total' => $blocksCount > 0 ? $blocksCount : count($processedResults), // Используем blocksCount из API
                    'blocks_count' => $blocksCount,
                    'prelaunches_count' => $data['data']['prelaunchesCount'] ?? 0,
                    'apartments_count' => $data['data']['apartmentsCount'] ?? 0,
                    'booked_apartments_count' => $data['data']['bookedApartmentsCount'] ?? 0,
                    'view_apartments_count' => $data['data']['viewApartmentsCount'] ?? 0,
                    'source' => 'api',
                    'raw_response' => $data,
                ];

            } catch (GuzzleException $e) {
                Log::error('Ошибка при запросе к API blocks/search', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                throw new \Exception('Ошибка при запросе к API: ' . $e->getMessage());
            }
            });
        } catch (\Exception $cacheException) {
            // Если кеш недоступен (например, БД не запущена), просто выполняем запрос без кеша
            Log::warning('Кеш недоступен, выполняем запрос без кеша', [
                'error' => $cacheException->getMessage(),
            ]);
            
            // Выполняем запрос напрямую без кеша (повторяем логику из замыкания)
            try {
                $authToken = $this->getAuthToken();
                if (empty($authToken)) {
                    throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
                }
                
                $apiUrl = 'https://api.trendagent.ru/v4_29/blocks/search/';
                $defaultParams = [
                    'show_type' => 'list',
                    'sort' => 'price',
                    'sort_order' => 'asc',
                    'count' => 20,
                    'offset' => 0,
                    'city' => '58c665588b6aa52311afa01b',
                    'lang' => 'ru',
                ];
                $queryParams = array_merge($defaultParams, $params);
                $queryParams['auth_token'] = $authToken;
                
                $roomParams = [];
                if (isset($queryParams['room']) && is_array($queryParams['room'])) {
                    $roomParams = $queryParams['room'];
                    unset($queryParams['room']);
                } elseif (isset($queryParams['room'])) {
                    $roomParams = [$queryParams['room']];
                    unset($queryParams['room']);
                }
                
                $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
                if (!empty($roomParams)) {
                    $roomQuery = [];
                    foreach ($roomParams as $room) {
                        $roomQuery[] = 'room=' . urlencode($room);
                    }
                    if (!empty($queryString)) {
                        $queryString .= '&' . implode('&', $roomQuery);
                    } else {
                        $queryString = implode('&', $roomQuery);
                    }
                }
                
                $fullUrl = $apiUrl . '?' . $queryString;
                $response = $this->client->get($fullUrl, [
                    'headers' => [
                        'Accept' => 'application/json, text/plain, */*',
                        'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                        'Origin' => 'https://spb.trendagent.ru',
                        'Referer' => 'https://spb.trendagent.ru/',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    ],
                    'timeout' => 30,
                    'verify' => false,
                    'allow_redirects' => true,
                ]);
                
                $statusCode = $response->getStatusCode();
                if ($statusCode === 304) {
                    return [
                        'success' => true,
                        'data' => [],
                        'total' => 0,
                        'source' => 'api',
                        'cached' => true,
                    ];
                }
                
                $body = $response->getBody()->getContents();
                if ($statusCode !== 200) {
                    throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
                }
                
                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Ошибка парсинга JSON ответа: ' . json_last_error_msg());
                }
                
                if (isset($data['errors']) && !empty($data['errors'])) {
                    throw new \Exception('API вернул ошибки: ' . json_encode($data['errors']));
                }
                
                $results = $data['data']['results'] ?? [];
                $processedResults = array_map(function($item) {
                    if (isset($item['image']) && is_array($item['image'])) {
                        $image = $item['image'];
                        if (isset($image['path']) && isset($image['file_name'])) {
                            $path = rtrim($image['path'], '/');
                            $fileName = $image['file_name'];
                            $path = ltrim($path, '/');
                            $item['image']['url'] = "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}";
                            $item['image']['url_full'] = "https://selcdn.trendagent.ru/images/{$path}/{$fileName}";
                        }
                    }
                    return $item;
                }, $results);
                
                $data['data']['results'] = $processedResults;
                return [
                    'success' => true,
                    'data' => $processedResults,
                    'total' => count($processedResults),
                    'blocks_count' => $data['data']['blocksCount'] ?? 0,
                    'prelaunches_count' => $data['data']['prelaunchesCount'] ?? 0,
                    'apartments_count' => $data['data']['apartmentsCount'] ?? 0,
                    'booked_apartments_count' => $data['data']['bookedApartmentsCount'] ?? 0,
                    'view_apartments_count' => $data['data']['viewApartmentsCount'] ?? 0,
                    'source' => 'api',
                    'raw_response' => $data,
                ];
            } catch (\Exception $e) {
                Log::error('Ошибка при выполнении запроса blocks/search без кеша', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * Получение данных паркинга через API parkings.trendagent.ru/search/blocks
     * 
     * @param array $params Параметры запроса (count, offset, sort, sort_order, city, lang и т.д.)
     * @return array Массив с данными паркинга
     * @throws \Exception
     */
    public function getParkingsSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для паркинга
            $apiUrl = 'https://parkings-api.trendagent.ru/search/blocks';
            
            // Параметры по умолчанию
            $defaultParams = [
                'sort' => 'price',
                'sort_order' => 'asc',
                'count' => 20,
                'offset' => 0,
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API parkings/search/blocks', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => 'https://parkings.trendagent.ru',
                    'Referer' => 'https://parkings.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 304) {
                Log::info('API паркинга вернул 304 Not Modified');
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'parkings_api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API паркинга', [
                    'status_code' => $statusCode,
                    'response_body' => substr($body, 0, 500),
                ]);
                throw new \Exception("Ошибка при запросе к API паркинга: HTTP {$statusCode}");
            }

            // Декодируем JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Обрабатываем структуру ответа паркинга
            $results = $data['results'] ?? [];
            $blocksCount = $data['blocksCount'] ?? 0;
            $placesCount = $data['placesCount'] ?? 0;
            $bookedPlacesCount = $data['bookedPlacesCount'] ?? 0;

            // Обрабатываем каждый результат паркинга
            $processedResults = [];
            foreach ($results as $item) {
                // Обрабатываем изображения (массив вместо объекта)
                $images = [];
                if (isset($item['images']) && is_array($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (isset($img['path']) && isset($img['file_name'])) {
                            $path = rtrim($img['path'], '/');
                            $path = ltrim($path, '/');
                            $fileName = $img['file_name'];
                            $images[] = [
                                'thumbnail' => "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}",
                                'full' => "https://selcdn.trendagent.ru/images/{$path}/{$fileName}",
                                'path' => $img['path'],
                                'file_name' => $img['file_name'],
                            ];
                        }
                    }
                }

                // Обрабатываем минимальные цены
                $minPrice = null;
                $minPrices = [];
                if (isset($item['min_prices']) && is_array($item['min_prices'])) {
                    foreach ($item['min_prices'] as $priceItem) {
                        $price = $priceItem['price'] ?? null;
                        $placeType = $priceItem['place_type']['value'] ?? null;
                        if ($price !== null) {
                            $minPrices[] = [
                                'price' => $price,
                                'place_type' => $placeType,
                            ];
                            if ($minPrice === null || $price < $minPrice) {
                                $minPrice = $price;
                            }
                        }
                    }
                }

                // Обрабатываем reward (объект вместо массива)
                $reward = null;
                if (isset($item['reward']['label'])) {
                    $reward = $item['reward']['label'];
                }

                // Обрабатываем parking_types
                $parkingTypes = [];
                if (isset($item['parking_types']) && is_array($item['parking_types'])) {
                    foreach ($item['parking_types'] as $type) {
                        $parkingTypes[] = $type['value'] ?? $type['label'] ?? null;
                    }
                }
                $parkingTypes = array_filter($parkingTypes);

                $processedResults[] = [
                    'id' => $item['block_id'] ?? null,
                    'guid' => $item['block_guid'] ?? null,
                    'name' => $item['block_name'] ?? null,
                    'address' => $item['address'] ?? [],
                    'places_count' => $item['places_count'] ?? 0,
                    'builder' => $item['builder'] ?? null,
                    'city' => $item['city'] ?? null,
                    'region' => $item['region'] ?? null,
                    'location' => $item['location'] ?? [],
                    'deadline' => $item['deadline'] ?? null,
                    'min_price' => $minPrice,
                    'min_prices' => $minPrices,
                    'reward' => $reward,
                    'parking_types' => $parkingTypes,
                    'images' => $images,
                    'subways' => $item['subways'] ?? [],
                    'geometry' => $item['geometry'] ?? null,
                    'sales_start_at' => $item['sales_start_at'] ?? [],
                    'property_types' => $item['property_types'] ?? [],
                ];
            }

            Log::info('Данные паркинга получены', [
                'blocks_count' => $blocksCount,
                'places_count' => $placesCount,
                'booked_places_count' => $bookedPlacesCount,
                'results_count' => count($processedResults),
            ]);

            return [
                'success' => true,
                'data' => $processedResults,
                'total' => count($processedResults),
                'blocks_count' => $blocksCount,
                'places_count' => $placesCount,
                'booked_places_count' => $bookedPlacesCount,
                'source' => 'parkings_api',
                'raw_response' => $data,
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка Guzzle при получении данных паркинга', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при получении данных паркинга: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении данных паркинга', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получение данных участков через API house-api.trendagent.ru/v1/search/plots
     * 
     * @param array $params Параметры запроса (count, offset, sort_type, sort_order, city, lang и т.д.)
     * @return array Массив с данными участков
     * @throws \Exception
     */
    public function getPlotsSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для участков
            $apiUrl = 'https://house-api.trendagent.ru/v1/search/plots';
            
            // Параметры по умолчанию
            $defaultParams = [
                'sort_type' => 'price', // Для участков используется sort_type вместо sort
                'sort_order' => 'asc',
                'count' => 20,
                'offset' => 0,
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            
            // Преобразуем sort в sort_type для участков
            if (isset($queryParams['sort']) && !isset($queryParams['sort_type'])) {
                $queryParams['sort_type'] = $queryParams['sort'];
                unset($queryParams['sort']);
            }
            
            $queryParams['auth_token'] = $authToken;
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API plots/search/plots', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => 'https://house.trendagent.ru',
                    'Referer' => 'https://house.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 304) {
                Log::info('API участков вернул 304 Not Modified');
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'plots_api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API участков', [
                    'status_code' => $statusCode,
                    'response_body' => substr($body, 0, 500),
                ]);
                throw new \Exception("Ошибка при запросе к API участков: HTTP {$statusCode}");
            }

            // Декодируем JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Обрабатываем структуру ответа участков
            $results = $data['list'] ?? [];
            $totalCount = $data['total_count'] ?? 0;
            $resultCount = $data['result_count'] ?? 0;
            $plotsCount = $data['plots_count'] ?? 0;

            // Логирование первого элемента сырых данных для отладки
            if (count($results) > 0) {
                $firstRawItem = $results[0];
                Log::info('Plots API - Raw first item', [
                    'keys' => array_keys($firstRawItem),
                    'has_name' => isset($firstRawItem['name']),
                    'name' => $firstRawItem['name'] ?? null,
                    'has_min_prices' => isset($firstRawItem['min_prices']),
                    'min_prices' => $firstRawItem['min_prices'] ?? null,
                    'has_images' => isset($firstRawItem['images']),
                    'images_count' => is_array($firstRawItem['images'] ?? null) ? count($firstRawItem['images']) : 0,
                    'full_item' => $firstRawItem,
                ]);
            }

            // Обрабатываем каждый результат участков
            $processedResults = [];
            foreach ($results as $item) {
                // Обрабатываем изображения
                $images = [];
                if (isset($item['images']) && is_array($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (isset($img['path']) && isset($img['file_name'])) {
                            $path = rtrim($img['path'], '/');
                            $path = ltrim($path, '/');
                            $fileName = $img['file_name'];
                            $images[] = [
                                'thumbnail' => "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}",
                                'full' => "https://selcdn.trendagent.ru/images/{$path}/{$fileName}",
                                'path' => $img['path'],
                                'file_name' => $img['file_name'],
                            ];
                        }
                    }
                }

                // Обрабатываем минимальные цены
                $minPrices = [];
                if (isset($item['min_prices']) && is_array($item['min_prices'])) {
                    foreach ($item['min_prices'] as $priceItem) {
                        $minPrices[] = [
                            'label' => $priceItem['label'] ?? null,
                            'value' => $priceItem['value'] ?? null,
                            'unit' => $priceItem['unit'] ?? '₽',
                        ];
                    }
                }

                // Обрабатываем reward
                $reward = null;
                $rewardHint = null;
                if (isset($item['reward'])) {
                    $reward = $item['reward']['label'] ?? null;
                    $rewardHint = $item['reward']['hint'] ?? null;
                }

                // Обрабатываем distance
                $distance = null;
                if (isset($item['distance'])) {
                    $distance = [
                        'center' => $item['distance']['center'] ?? null,
                        'railway' => $item['distance']['railway'] ?? null,
                        'highway' => $item['distance']['highway'] ?? null,
                    ];
                }

                // Обрабатываем deadline безопасно
                $deadlineValue = null;
                if (isset($item['deadline'])) {
                    if (is_array($item['deadline']) && isset($item['deadline']['value'])) {
                        $deadlineValue = $item['deadline']['value'];
                    } elseif (is_string($item['deadline'])) {
                        $deadlineValue = $item['deadline'];
                    }
                }

                $processedResults[] = [
                    'id' => $item['_id'] ?? null,
                    '_id' => $item['_id'] ?? null, // Добавляем _id для совместимости
                    'guid' => $item['guid'] ?? null,
                    'name' => $item['name'] ?? $item['village_name'] ?? $item['block_name'] ?? null,
                    'village_name' => $item['village_name'] ?? null,
                    'address' => $item['address'] ?? null,
                    'plots_count' => $item['plots_count'] ?? 0,
                    'view_plots_count' => $item['view_plots_count'] ?? 0,
                    'builder' => $item['builder'] ?? null,
                    'distance' => $distance,
                    'deadline' => $deadlineValue,
                    'min_prices' => $minPrices,
                    'reward' => $reward,
                    'reward_hint' => $rewardHint,
                    'sales_start' => $item['sales_start'] ?? null,
                    'images' => $images,
                    'is_new_village' => $item['is_new_village'] ?? false,
                    'property_types' => $item['property_types'] ?? [],
                ];
            }

            // Логирование первого элемента для отладки
            if (count($processedResults) > 0) {
                $firstItem = $processedResults[0];
                Log::info('Данные участков получены - первый элемент', [
                    'total_count' => $totalCount,
                    'result_count' => $resultCount,
                    'plots_count' => $plotsCount,
                    'results_count' => count($processedResults),
                    'first_item_keys' => array_keys($firstItem),
                    'first_item_name' => $firstItem['name'] ?? null,
                    'first_item_min_prices' => $firstItem['min_prices'] ?? null,
                    'first_item_images_count' => is_array($firstItem['images'] ?? null) ? count($firstItem['images']) : 0,
                    'first_item_full' => $firstItem,
                ]);
            } else {
                Log::info('Данные участков получены - пустой результат', [
                    'total_count' => $totalCount,
                    'result_count' => $resultCount,
                    'plots_count' => $plotsCount,
                    'raw_data_keys' => array_keys($data ?? []),
                    'raw_data_list_count' => count($data['list'] ?? []),
                ]);
            }

            // Определяем общее количество участков
            $finalTotal = $plotsCount > 0 ? $plotsCount : ($totalCount > 0 ? $totalCount : count($processedResults));

            return [
                'success' => true,
                'data' => $processedResults,
                'total' => $finalTotal,
                'total_count' => $totalCount,
                'result_count' => $resultCount,
                'plots_count' => $plotsCount,
                'source' => 'plots_api',
                'raw_response' => $data,
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка Guzzle при получении данных участков', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при получении данных участков: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении данных участков', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получение детальной информации об участке
     * 
     * @param string $plotId ID участка
     * @param array $params Дополнительные параметры
     * @return array Детальная информация об участке
     * @throws \Exception
     */
    public function getPlotDetail(string $plotId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для детальной информации об участке
            $apiUrl = "https://house-api.trendagent.ru/v1/plots/{$plotId}";
            
            $queryParams = [
                'auth_token' => $authToken,
                'lang' => 'ru',
            ];
            
            // Добавляем дополнительные параметры
            if (isset($params['city'])) {
                $queryParams['city'] = $params['city'];
            }
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API plot detail', [
                'url' => $apiUrl,
                'plot_id' => $plotId,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => 'https://house.trendagent.ru',
                    'Referer' => 'https://house.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API детальной информации об участке', [
                    'status_code' => $statusCode,
                    'url' => $fullUrl,
                    'plot_id' => $plotId,
                    'response_body' => substr($body, 0, 1000),
                ]);
                
                // Если 404, пробуем найти участок в списке
                if ($statusCode === 404) {
                    Log::warning('Endpoint для детальной информации участка не найден, используем данные из списка');
                    try {
                        $searchResult = $this->getPlotsSearch([
                            'count' => 1000,
                            'offset' => 0,
                        ]);
                        
                        if ($searchResult['success'] && !empty($searchResult['data'])) {
                            foreach ($searchResult['data'] as $plot) {
                                if ((isset($plot['_id']) && $plot['_id'] === $plotId) ||
                                    (isset($plot['id']) && $plot['id'] === $plotId)) {
                                    // Используем данные из списка как детальную информацию
                                    return [
                                        'success' => true,
                                        'block_id' => $plot['_id'] ?? $plot['id'] ?? null,
                                        'block_guid' => $plot['guid'] ?? $plotId,
                                        'data' => [
                                            'unified' => [
                                                'data' => $plot,
                                            ],
                                        ],
                                        'raw_response' => $plot,
                                        'source' => 'search_list',
                                    ];
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Не удалось получить данные из списка', ['error' => $e->getMessage()]);
                    }
                }
                
                throw new \Exception("Ошибка при запросе к API детальной информации об участке: HTTP {$statusCode}");
            }

            // Декодируем JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Обрабатываем данные участка аналогично getPlotsSearch
            $plot = $data;
            
            // Обрабатываем изображения
            $images = [];
            if (isset($plot['images']) && is_array($plot['images'])) {
                foreach ($plot['images'] as $img) {
                    if (isset($img['path']) && isset($img['file_name'])) {
                        $path = rtrim($img['path'], '/');
                        $path = ltrim($path, '/');
                        $fileName = $img['file_name'];
                        $images[] = [
                            'thumbnail' => "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}",
                            'full' => "https://selcdn.trendagent.ru/images/{$path}/{$fileName}",
                            'path' => $img['path'],
                            'file_name' => $img['file_name'],
                        ];
                    }
                }
            }

            // Обрабатываем минимальные цены
            $minPrices = [];
            if (isset($plot['min_prices']) && is_array($plot['min_prices'])) {
                foreach ($plot['min_prices'] as $priceItem) {
                    $minPrices[] = [
                        'label' => $priceItem['label'] ?? null,
                        'value' => $priceItem['value'] ?? null,
                        'unit' => $priceItem['unit'] ?? '₽',
                    ];
                }
            }

            $processedPlot = [
                'id' => $plot['_id'] ?? $plotId,
                '_id' => $plot['_id'] ?? $plotId,
                'guid' => $plot['guid'] ?? null,
                'name' => $plot['name'] ?? $plot['village_name'] ?? null,
                'village_name' => $plot['village_name'] ?? null,
                'address' => $plot['address'] ?? null,
                'area' => $plot['area'] ?? $plot['area_total'] ?? null,
                'area_total' => $plot['area_total'] ?? $plot['area'] ?? null,
                'price' => $plot['price'] ?? null,
                'min_prices' => $minPrices,
                'images' => $images,
                'status' => $plot['status'] ?? null,
                'plan' => $plot['plan'] ?? $plot['plan_image'] ?? null,
                'village_id' => $plot['village_id'] ?? null,
                'builder' => $plot['builder'] ?? null,
                'deadline' => $plot['deadline'] ?? null,
                'reward' => $plot['reward'] ?? null,
            ];

            return [
                'success' => true,
                'block_id' => $processedPlot['_id'],
                'block_guid' => $processedPlot['guid'],
                'data' => [
                    'unified' => [
                        'data' => $processedPlot,
                    ],
                ],
                'raw_response' => $plot,
                'source' => 'api',
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка Guzzle при получении детальной информации об участке', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'plot_id' => $plotId,
            ]);
            throw new \Exception('Ошибка при получении детальной информации об участке: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении детальной информации об участке', [
                'message' => $e->getMessage(),
                'plot_id' => $plotId,
            ]);
            throw $e;
        }
    }

    /**
     * Получение данных поселка по ID из списка villages
     * 
     * @param string $villageId ID поселка
     * @param array $params Дополнительные параметры
     * @return array Данные поселка или null если не найден
     */
    public function getVillageById(string $villageId, array $params = []): ?array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем список поселков и ищем нужный
            $searchParams = array_merge([
                'count' => 100,
                'offset' => 0,
            ], $params);
            
            $villagesList = $this->getVillagesSearch($searchParams);
            
            if (isset($villagesList['data']) && is_array($villagesList['data'])) {
                foreach ($villagesList['data'] as $village) {
                    if (isset($village['_id']) && $village['_id'] === $villageId) {
                        return $village;
                    }
                    if (isset($village['id']) && $village['id'] === $villageId) {
                        return $village;
                    }
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Ошибка получения поселка по ID', [
                'village_id' => $villageId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получение данных поселков (villages) через API house-api.trendagent.ru/v1/search/villages
     * 
     * @param array $params Параметры запроса (count, offset, sort_type, sort_order, city, lang и т.д.)
     * @return array Массив с данными поселков
     * @throws \Exception
     */
    public function getVillagesSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для поселков
            $apiUrl = 'https://house-api.trendagent.ru/v1/search/villages';
            
            // Параметры по умолчанию
            $defaultParams = [
                'sort_type' => 'price',
                'sort_order' => 'asc',
                'count' => 20,
                'offset' => 0,
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            
            // Преобразуем sort в sort_type
            if (isset($queryParams['sort']) && !isset($queryParams['sort_type'])) {
                $queryParams['sort_type'] = $queryParams['sort'];
                unset($queryParams['sort']);
            }
            
            $queryParams['auth_token'] = $authToken;
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API villages/search/villages', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => 'https://house.trendagent.ru',
                    'Referer' => 'https://house.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 304) {
                Log::info('API поселков вернул 304 Not Modified');
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'villages_api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API поселков', [
                    'status_code' => $statusCode,
                    'response_body' => substr($body, 0, 500),
                ]);
                throw new \Exception("Ошибка при запросе к API поселков: HTTP {$statusCode}");
            }

            // Декодируем JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Логируем структуру ответа для отладки
            Log::info('Структура ответа API поселков', [
                'data_keys' => array_keys($data ?? []),
                'has_list' => isset($data['list']),
                'list_count' => isset($data['list']) && is_array($data['list']) ? count($data['list']) : 0,
                'total_count' => $data['total_count'] ?? null,
                'result_count' => $data['result_count'] ?? null,
                'plots_count' => $data['plots_count'] ?? null,
                'villages_count' => $data['villages_count'] ?? null,
                'total' => $data['total'] ?? null,
            ]);

            // Обрабатываем структуру ответа поселков
            $results = $data['list'] ?? [];
            $totalCount = $data['total_count'] ?? $data['total'] ?? 0;
            $resultCount = $data['result_count'] ?? 0;
            $plotsCount = $data['plots_count'] ?? 0;
            $villagesCount = $data['villages_count'] ?? $data['total_count'] ?? 0;

            // Логирование первого элемента сырых данных для отладки
            if (count($results) > 0) {
                $firstRawItem = $results[0];
                Log::info('Villages API - Raw first item', [
                    'keys' => array_keys($firstRawItem),
                    'has_name' => isset($firstRawItem['name']),
                    'name' => $firstRawItem['name'] ?? null,
                    'has_village_name' => isset($firstRawItem['village_name']),
                    'village_name' => $firstRawItem['village_name'] ?? null,
                    'has_min_prices' => isset($firstRawItem['min_prices']),
                    'min_prices' => $firstRawItem['min_prices'] ?? null,
                    'has_min_price' => isset($firstRawItem['min_price']),
                    'min_price' => $firstRawItem['min_price'] ?? null,
                    'has_price' => isset($firstRawItem['price']),
                    'price' => $firstRawItem['price'] ?? null,
                    'has_price_from' => isset($firstRawItem['price_from']),
                    'price_from' => $firstRawItem['price_from'] ?? null,
                    'has_images' => isset($firstRawItem['images']),
                    'images_count' => is_array($firstRawItem['images'] ?? null) ? count($firstRawItem['images']) : 0,
                    'has_renderer' => isset($firstRawItem['renderer']),
                    'renderer_count' => is_array($firstRawItem['renderer'] ?? null) ? count($firstRawItem['renderer']) : 0,
                ]);
            }

            // Обрабатываем каждый результат поселков
            $processedResults = [];
            $totalPlotsFromItems = 0;
            foreach ($results as $item) {
                // Обрабатываем изображения
                $images = [];
                if (isset($item['images']) && is_array($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (isset($img['path']) && isset($img['file_name'])) {
                            $path = rtrim($img['path'], '/');
                            $path = ltrim($path, '/');
                            $fileName = $img['file_name'];
                            $images[] = [
                                'thumbnail' => "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}",
                                'full' => "https://selcdn.trendagent.ru/images/{$path}/{$fileName}",
                                'path' => $img['path'],
                                'file_name' => $img['file_name'],
                            ];
                        }
                    }
                }

                // Обрабатываем минимальные цены
                $minPrices = [];
                if (isset($item['min_prices']) && is_array($item['min_prices'])) {
                    foreach ($item['min_prices'] as $priceItem) {
                        // API возвращает unformatted_value (число) и value (отформатированная строка)
                        $priceValue = null;
                        if (isset($priceItem['unformatted_value']) && $priceItem['unformatted_value'] !== null) {
                            $priceValue = (float)$priceItem['unformatted_value'];
                        } elseif (isset($priceItem['value'])) {
                            // Если value - число, используем его
                            if (is_numeric($priceItem['value'])) {
                                $priceValue = (float)$priceItem['value'];
                            } else {
                                // Если value - строка типа "от 177 000", пытаемся извлечь число
                                $priceValue = (float)preg_replace('/[^\d.]/', '', $priceItem['value']);
                            }
                        }
                        
                        // Проверяем, что priceValue валидное число и больше 0
                        if ($priceValue !== null && $priceValue > 0) {
                            $minPrices[] = [
                                'label' => $priceItem['label'] ?? null,
                                'value' => $priceValue,
                                'formatted_value' => $priceItem['value'] ?? null, // Сохраняем отформатированное значение
                                'unit' => $priceItem['unit'] ?? '₽',
                            ];
                        }
                    }
                }
                
                // Если min_prices пустой, но есть другие поля с ценой, добавляем их
                if (empty($minPrices)) {
                    if (isset($item['min_price']) && $item['min_price'] > 0) {
                        $minPrices[] = [
                            'label' => 'от',
                            'value' => $item['min_price'],
                            'unit' => '₽',
                        ];
                    } elseif (isset($item['price']) && $item['price'] > 0) {
                        $minPrices[] = [
                            'label' => null,
                            'value' => $item['price'],
                            'unit' => '₽',
                        ];
                    } elseif (isset($item['price_from']) && $item['price_from'] > 0) {
                        $minPrices[] = [
                            'label' => 'от',
                            'value' => $item['price_from'],
                            'unit' => '₽',
                        ];
                    }
                }

                // Обрабатываем reward
                $reward = null;
                $rewardHint = null;
                if (isset($item['reward'])) {
                    $reward = $item['reward']['label'] ?? null;
                    $rewardHint = $item['reward']['hint'] ?? null;
                }

                // Обрабатываем distance
                $distance = null;
                if (isset($item['distance'])) {
                    $distance = [
                        'center' => $item['distance']['center'] ?? null,
                        'railway' => $item['distance']['railway'] ?? null,
                        'highway' => $item['distance']['highway'] ?? null,
                    ];
                }

                $itemPlotsCount = $item['plots_count'] ?? $item['view_plots_count'] ?? 0;
                $totalPlotsFromItems += (int)$itemPlotsCount;

                // Обрабатываем deadline безопасно
                $deadlineValue = null;
                if (isset($item['deadline'])) {
                    if (is_array($item['deadline']) && isset($item['deadline']['value'])) {
                        $deadlineValue = $item['deadline']['value'];
                    } elseif (is_string($item['deadline'])) {
                        $deadlineValue = $item['deadline'];
                    }
                }

                $processedResults[] = [
                    'id' => $item['_id'] ?? null,
                    '_id' => $item['_id'] ?? null, // Добавляем _id для совместимости
                    'guid' => $item['guid'] ?? null,
                    'name' => $item['name'] ?? $item['village_name'] ?? $item['block_name'] ?? null,
                    'village_name' => $item['village_name'] ?? null,
                    'address' => $item['address'] ?? null,
                    'plots_count' => $itemPlotsCount,
                    'view_plots_count' => $item['view_plots_count'] ?? 0,
                    'builder' => $item['builder'] ?? null,
                    'distance' => $distance,
                    'deadline' => $deadlineValue,
                    'min_prices' => $minPrices,
                    'reward' => $reward,
                    'reward_hint' => $rewardHint,
                    'sales_start' => $item['sales_start'] ?? null,
                    'images' => $images,
                    'is_new_village' => $item['is_new_village'] ?? false,
                    'property_types' => $item['property_types'] ?? [],
                    // Паспортные данные
                    'village_class' => $item['village_class'] ?? $item['class'] ?? null,
                    'unified_style' => $item['unified_style'] ?? $item['architectural_style'] ?? null,
                    'land_purpose' => $item['land_purpose'] ?? $item['purpose'] ?? null,
                    'water_supply' => $item['water_supply'] ?? null,
                    'sewerage' => $item['sewerage'] ?? null,
                    'gas_supply' => $item['gas_supply'] ?? null,
                    'electricity' => $item['electricity'] ?? null,
                    'power_kw' => $item['power_kw'] ?? $item['power'] ?? null,
                    'management_company' => $item['management_company'] ?? null,
                    'registration' => $item['registration'] ?? $item['permanent_registration'] ?? null,
                    'fiber_internet' => $item['fiber_internet'] ?? null,
                    'road' => $item['road'] ?? $item['road_type'] ?? null,
                    'payment' => $item['payment'] ?? $item['payment_types'] ?? null,
                    'contract' => $item['contract'] ?? $item['contract_type'] ?? null,
                    'escrow' => $item['escrow'] ?? null,
                    'description' => $item['description'] ?? $item['about'] ?? null,
                    'about' => $item['about'] ?? $item['description'] ?? null,
                ];
            }

            Log::info('Данные поселков получены', [
                'total_count' => $totalCount,
                'result_count' => $resultCount,
                'plots_count' => $plotsCount,
                'villages_count' => $villagesCount,
                'total_plots_from_items' => $totalPlotsFromItems,
                'results_count' => count($processedResults),
                'offset' => $params['offset'] ?? 0,
            ]);

            // Определяем общее количество
            // Для поселков: plots_count (общее количество участков) > villages_count (количество поселков)
            $finalTotal = $plotsCount > 0 ? $plotsCount : ($villagesCount > 0 ? $villagesCount : ($totalCount > 0 ? $totalCount : count($processedResults)));

            return [
                'success' => true,
                'data' => $processedResults,
                'total' => $finalTotal,
                'total_count' => $totalCount,
                'result_count' => $resultCount,
                'plots_count' => $plotsCount,
                'villages_count' => $villagesCount,
                'source' => 'villages_api',
                'raw_response' => $data,
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка Guzzle при получении данных поселков', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при получении данных поселков: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении данных поселков', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получение данных коммерции через API commerce.trendagent.ru/search/blocks
     * 
     * @param array $params Параметры запроса (count, offset, sort, sort_order, city, lang и т.д.)
     * @return array Массив с данными коммерции
     * @throws \Exception
     */
    public function getCommercialSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для коммерции
            $apiUrl = 'https://commerce.trendagent.ru/search/blocks';
            
            // Параметры по умолчанию
            $defaultParams = [
                'sort' => 'price',
                'sort_order' => 'asc',
                'count' => 20,
                'offset' => 0,
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API commerce/search/blocks', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => 'https://commerce.trendagent.ru',
                    'Referer' => 'https://commerce.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 304) {
                Log::info('API коммерции вернул 304 Not Modified');
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'commercial_api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API коммерции', [
                    'status_code' => $statusCode,
                    'response_body' => substr($body, 0, 500),
                ]);
                throw new \Exception("Ошибка при запросе к API коммерции: HTTP {$statusCode}");
            }

            // Декодируем JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Обрабатываем структуру ответа коммерции
            $results = $data['result'] ?? [];
            $premisesCount = $data['premises_count'] ?? 0;
            $blocksCount = $data['blocks_count'] ?? 0;
            $bookedPremisesCount = $data['booked_premises_count'] ?? 0;

            // Обрабатываем каждый результат коммерции
            $processedResults = [];
            foreach ($results as $item) {
                // Обрабатываем изображения
                $images = [];
                if (isset($item['images']) && is_array($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (isset($img['path']) && isset($img['file_name'])) {
                            $path = rtrim($img['path'], '/');
                            $path = ltrim($path, '/');
                            $fileName = $img['file_name'];
                            $images[] = [
                                'thumbnail' => "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}",
                                'full' => "https://selcdn.trendagent.ru/images/{$path}/{$fileName}",
                                'path' => $img['path'],
                                'file_name' => $img['file_name'],
                            ];
                        }
                    }
                }

                // Обрабатываем минимальные цены (с purpose)
                $minPrices = [];
                $minPrice = null;
                if (isset($item['min_prices']) && is_array($item['min_prices'])) {
                    foreach ($item['min_prices'] as $priceItem) {
                        $price = $priceItem['price'] ?? null;
                        $purpose = $priceItem['purpose'] ?? null;
                        if ($price !== null) {
                            $minPrices[] = [
                                'price' => $price,
                                'purpose' => $purpose,
                                'value' => $priceItem['value'] ?? null,
                            ];
                            if ($minPrice === null || $price < $minPrice) {
                                $minPrice = $price;
                            }
                        }
                    }
                }

                // Обрабатываем deadline (массив)
                $deadline = null;
                $deadlineOverCheck = false;
                if (isset($item['deadline']) && is_array($item['deadline']) && !empty($item['deadline'])) {
                    // Берем первый deadline
                    $firstDeadline = $item['deadline'][0];
                    if (isset($firstDeadline['deadline'])) {
                        $deadline = $firstDeadline['deadline'];
                    }
                    $deadlineOverCheck = $firstDeadline['deadline_over_check'] ?? false;
                }

                // Обрабатываем reward
                $reward = null;
                if (isset($item['reward']['label'])) {
                    $reward = $item['reward']['label'];
                }

                $processedResults[] = [
                    'id' => $item['block_id'] ?? null,
                    'guid' => $item['guid'] ?? null,
                    'name' => $item['block_name'] ?? null,
                    'address' => $item['address'] ?? null,
                    'premises_count' => $item['premises_count'] ?? 0,
                    'builder' => $item['builder'] ?? null,
                    'district' => $item['district'] ?? null,
                    'location' => $item['location'] ?? [],
                    'deadline' => $deadline,
                    'deadline_over_check' => $deadlineOverCheck,
                    'min_price' => $minPrice,
                    'min_prices' => $minPrices,
                    'reward' => $reward,
                    'images' => $images,
                    'subways' => $item['subways'] ?? [],
                    'sales_start_at' => $item['sales_start_at'] ?? [],
                    'property_types' => $item['property_types'] ?? [],
                    'new_block' => $item['new_block'] ?? false,
                ];
            }

            Log::info('Данные коммерции получены', [
                'premises_count' => $premisesCount,
                'blocks_count' => $blocksCount,
                'booked_premises_count' => $bookedPremisesCount,
                'results_count' => count($processedResults),
            ]);

            // Для коммерции total = blocks_count (количество комплексов), а не количество результатов на странице
            $finalTotal = $blocksCount > 0 ? $blocksCount : count($processedResults);

            return [
                'success' => true,
                'data' => $processedResults,
                'total' => $finalTotal,
                'premises_count' => $premisesCount,
                'blocks_count' => $blocksCount,
                'booked_premises_count' => $bookedPremisesCount,
                'source' => 'commercial_api',
                'raw_response' => $data,
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка Guzzle при получении данных коммерции', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при получении данных коммерции: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении данных коммерции', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получение данных домов через API api.trendagent.ru/v4_29/blocks/search/
     * 
     * ВАЖНО: Дома используют тот же эндпоинт, что и квартиры, но с фильтром room=[30,40]
     * 30 = Коттеджи, 40 = Таунхаусы
     * 
     * @param array $params Параметры запроса (count, offset, sort, sort_order, city, lang и т.д.)
     * @return array Массив с данными домов
     * @throws \Exception
     */
    public function getHousesSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для домов
            // ВАЖНО: Дома используют api.trendagent.ru/v4_29/blocks/search/ с фильтром room=[30,40]
            $apiUrl = 'https://api.trendagent.ru/v4_29/blocks/search/';
            
            // Параметры по умолчанию
            $defaultParams = [
                'show_type' => 'list',
                'sort' => 'price',
                'sort_order' => 'asc',
                'count' => 20,
                'offset' => 0,
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
                'room' => [30, 40], // Фильтр для домов: 30=Коттеджи, 40=Таунхаусы
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            
            // Если room не передан в параметрах, используем фильтр по умолчанию для домов
            if (!isset($queryParams['room'])) {
                $queryParams['room'] = [30, 40];
            }
            
            // Обрабатываем параметр room для правильного формирования URL
            // room может быть массивом [30, 40] и должен стать room=30&room=40
            $roomParams = [];
            if (isset($queryParams['room']) && is_array($queryParams['room'])) {
                $roomParams = $queryParams['room'];
                unset($queryParams['room']); // Удаляем из основных параметров
            } elseif (isset($queryParams['room'])) {
                // Если одно значение, делаем массив
                $roomParams = [$queryParams['room']];
                unset($queryParams['room']);
            }
            
            $queryParams['auth_token'] = $authToken;
            
            // Формируем базовый query string
            $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
            
            // Добавляем параметры room отдельно (room=30&room=40)
            if (!empty($roomParams)) {
                $roomQuery = [];
                foreach ($roomParams as $room) {
                    $roomQuery[] = 'room=' . urlencode($room);
                }
                if (!empty($queryString)) {
                    $queryString .= '&' . implode('&', $roomQuery);
                } else {
                    $queryString = implode('&', $roomQuery);
                }
            }
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . $queryString;

            Log::info('Запрос к API houses через blocks/search', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
                'room_filter' => $roomParams,
                'params' => array_merge($queryParams, ['auth_token' => '***']),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => 'https://spb.trendagent.ru',
                    'Referer' => 'https://spb.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 304) {
                Log::info('API домов вернул 304 Not Modified');
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'houses_api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API домов', [
                    'status_code' => $statusCode,
                    'url' => $fullUrl,
                    'response_body' => substr($body, 0, 1000),
                ]);
                throw new \Exception("Ошибка при запросе к API домов: HTTP {$statusCode}. Ответ: " . substr($body, 0, 200));
            }

            // Декодируем JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Ошибка декодирования JSON ответа API домов', [
                    'json_error' => json_last_error_msg(),
                    'response_preview' => substr($body, 0, 500),
                ]);
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Проверяем наличие ошибок в ответе
            if (isset($data['error']) || isset($data['errors'])) {
                $errorMessage = $data['error'] ?? (is_array($data['errors']) ? implode(', ', $data['errors']) : 'Неизвестная ошибка');
                Log::error('API домов вернул ошибку', [
                    'error' => $errorMessage,
                    'data' => $data,
                ]);
                throw new \Exception('API домов вернул ошибку: ' . $errorMessage);
            }

            // Логируем структуру ответа для отладки
            Log::info('Структура ответа API домов (blocks/search)', [
                'data_keys' => array_keys($data ?? []),
                'has_data' => isset($data['data']),
                'has_results' => isset($data['data']['results']),
                'results_count' => isset($data['data']['results']) && is_array($data['data']['results']) ? count($data['data']['results']) : 0,
                'blocks_count' => $data['data']['blocksCount'] ?? null,
                'apartments_count' => $data['data']['apartmentsCount'] ?? null,
            ]);

            // Обрабатываем структуру ответа домов (структура как в blocks/search)
            $results = $data['data']['results'] ?? [];
            $blocksCount = $data['data']['blocksCount'] ?? 0;
            $apartmentsCount = $data['data']['apartmentsCount'] ?? 0;
            
            // Для домов apartmentsCount - это количество домов (коттеджей и таунхаусов)
            $housesCount = $apartmentsCount;

            // Обрабатываем каждый результат домов (структура как в blocks/search)
            $processedResults = array_map(function($item) {
                // Обрабатываем изображение, если оно есть (структура как в blocks/search)
                if (isset($item['image']) && is_array($item['image'])) {
                    $image = $item['image'];
                    if (isset($image['path']) && isset($image['file_name'])) {
                        $path = rtrim($image['path'], '/');
                        $path = ltrim($path, '/');
                        $fileName = $image['file_name'];
                        $item['image']['url'] = "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}";
                        $item['image']['url_full'] = "https://selcdn.trendagent.ru/images/{$path}/{$fileName}";
                    }
                }
                
                // Обрабатываем изображения (если есть массив images)
                $images = [];
                if (isset($item['images']) && is_array($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (isset($img['path']) && isset($img['file_name'])) {
                            $path = rtrim($img['path'], '/');
                            $path = ltrim($path, '/');
                            $fileName = $img['file_name'];
                            $images[] = [
                                'thumbnail' => "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}",
                                'full' => "https://selcdn.trendagent.ru/images/{$path}/{$fileName}",
                                'path' => $img['path'],
                                'file_name' => $img['file_name'],
                            ];
                        }
                    }
                }
                
                // Если есть одиночное изображение, добавляем его в массив images
                if (isset($item['image']) && !empty($images)) {
                    // images уже заполнен
                } elseif (isset($item['image']['url'])) {
                    $images[] = [
                        'thumbnail' => $item['image']['url'],
                        'full' => $item['image']['url_full'] ?? $item['image']['url'],
                    ];
                }

                // Обрабатываем минимальные цены
                $minPrices = [];
                if (isset($item['min_prices']) && is_array($item['min_prices'])) {
                    foreach ($item['min_prices'] as $priceItem) {
                        // Для домов может быть price или value
                        $priceValue = $priceItem['price'] ?? $priceItem['value'] ?? null;
                        // Если value - строка типа "от 177 000", пытаемся извлечь число
                        if ($priceValue === null && isset($priceItem['value']) && is_string($priceItem['value'])) {
                            $priceValue = (float)preg_replace('/[^\d.]/', '', $priceItem['value']);
                        }
                        // Если есть unformatted_value, используем его
                        if (isset($priceItem['unformatted_value']) && $priceItem['unformatted_value'] !== null) {
                            $priceValue = (float)$priceItem['unformatted_value'];
                        }
                        
                        if ($priceValue !== null && $priceValue > 0) {
                            $minPrices[] = [
                                'label' => $priceItem['label'] ?? null,
                                'value' => $priceValue,
                                'price' => $priceValue, // Добавляем price для совместимости
                                'formatted_value' => $priceItem['value'] ?? null, // Сохраняем отформатированное значение
                                'unit' => $priceItem['unit'] ?? '₽',
                            ];
                        }
                    }
                }
                
                // Если min_prices пустой, но есть другие поля с ценой, добавляем их
                if (empty($minPrices)) {
                    if (isset($item['min_price']) && $item['min_price'] > 0) {
                        $minPrices[] = [
                            'label' => 'от',
                            'value' => $item['min_price'],
                            'price' => $item['min_price'],
                            'unit' => '₽',
                        ];
                    } elseif (isset($item['price']) && $item['price'] > 0) {
                        $minPrices[] = [
                            'label' => null,
                            'value' => $item['price'],
                            'price' => $item['price'],
                            'unit' => '₽',
                        ];
                    } elseif (isset($item['price_from']) && $item['price_from'] > 0) {
                        $minPrices[] = [
                            'label' => 'от',
                            'value' => $item['price_from'],
                            'price' => $item['price_from'],
                            'unit' => '₽',
                        ];
                    }
                }

                // Обрабатываем reward
                $reward = null;
                $rewardHint = null;
                if (isset($item['reward'])) {
                    $reward = is_array($item['reward']) ? ($item['reward']['label'] ?? null) : $item['reward'];
                    $rewardHint = is_array($item['reward']) ? ($item['reward']['hint'] ?? null) : null;
                }

                // Обрабатываем distance
                $distance = null;
                if (isset($item['distance'])) {
                    $distance = [
                        'center' => $item['distance']['center'] ?? null,
                        'railway' => $item['distance']['railway'] ?? null,
                        'highway' => $item['distance']['highway'] ?? null,
                    ];
                }

                // Обрабатываем deadline безопасно
                $deadline = null;
                if (isset($item['deadline'])) {
                    if (is_array($item['deadline'])) {
                        if (isset($item['deadline']['value'])) {
                            $deadline = $item['deadline']['value'];
                        } elseif (isset($item['deadline'][0]) && is_array($item['deadline'][0])) {
                            $deadline = $item['deadline'][0]['value'] ?? $item['deadline'][0]['deadline'] ?? null;
                        } elseif (count($item['deadline']) > 0) {
                            $deadline = $item['deadline'][0];
                        }
                    } else {
                        $deadline = $item['deadline'];
                    }
                }

                // Возвращаем обработанный элемент
                return [
                    'id' => $item['_id'] ?? $item['id'] ?? null,
                    'guid' => $item['guid'] ?? null,
                    'name' => $item['name'] ?? null,
                    'address' => $item['address'] ?? null,
                    'houses_count' => $item['houses_count'] ?? $item['view_houses_count'] ?? 0,
                    'view_houses_count' => $item['view_houses_count'] ?? 0,
                    'builder' => $item['builder'] ?? null,
                    'distance' => $distance,
                    'deadline' => $deadline,
                    'min_prices' => $minPrices,
                    'reward' => $reward,
                    'reward_hint' => $rewardHint,
                    'sales_start' => $item['sales_start'] ?? null,
                    'images' => $images,
                    'image' => $item['image'] ?? null, // Сохраняем оригинальное изображение
                    'is_new_village' => $item['is_new_village'] ?? false,
                    'property_types' => $item['property_types'] ?? [],
                ];
            }, $results);

            Log::info('Данные домов получены', [
                'blocks_count' => $blocksCount,
                'houses_count' => $housesCount,
                'results_count' => count($processedResults),
                'offset' => $params['offset'] ?? 0,
            ]);

            // Для домов total = houses_count (количество домов), если доступно, иначе blocks_count
            $finalTotal = $housesCount > 0 ? $housesCount : $blocksCount;

            return [
                'success' => true,
                'data' => $processedResults,
                'total' => $finalTotal,
                'blocks_count' => $blocksCount,
                'houses_count' => $housesCount,
                'source' => 'houses_api',
                'raw_response' => $data,
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка Guzzle при получении данных домов', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при получении данных домов: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении данных домов', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получение данных подрядчиков (проектов домов) через API house-api.trendagent.ru/v1/projects/search
     * 
     * @param array $params Параметры запроса (count, offset, sort_type, sort_order, city, lang и т.д.)
     * @return array Массив с данными подрядчиков
     * @throws \Exception
     */
    public function getContractorsSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для подрядчиков
            $apiUrl = 'https://house-api.trendagent.ru/v1/projects/search';
            
            // Параметры по умолчанию
            $defaultParams = [
                'sort_type' => 'price', // Для подрядчиков используется sort_type вместо sort
                'sort_order' => 'asc',
                'count' => 20,
                'offset' => 0,
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            
            // Преобразуем sort в sort_type для подрядчиков
            if (isset($queryParams['sort']) && !isset($queryParams['sort_type'])) {
                $queryParams['sort_type'] = $queryParams['sort'];
                unset($queryParams['sort']);
            }
            
            $queryParams['auth_token'] = $authToken;
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API contractors/projects/search', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => 'https://spb.trendagent.ru',
                    'Referer' => 'https://spb.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 304) {
                Log::info('API подрядчиков вернул 304 Not Modified');
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'contractors_api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API подрядчиков', [
                    'status_code' => $statusCode,
                    'response_body' => substr($body, 0, 500),
                ]);
                throw new \Exception("Ошибка при запросе к API подрядчиков: HTTP {$statusCode}");
            }

            // Декодируем JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Обрабатываем структуру ответа подрядчиков
            $results = $data['list'] ?? $data['results'] ?? [];
            $totalCount = $data['total_count'] ?? $data['total'] ?? 0;
            
            // Логируем структуру первого элемента для отладки
            if (!empty($results) && is_array($results)) {
                $firstItem = $results[0];
                Log::info('Структура первого элемента подрядчика из API (raw)', [
                    'keys' => array_keys($firstItem),
                    'name_structure' => isset($firstItem['name']) ? (is_array($firstItem['name']) ? 'array' : gettype($firstItem['name'])) : 'not_set',
                    'has_price' => isset($firstItem['price']),
                    'has_prices' => isset($firstItem['prices']),
                    'has_contractor' => isset($firstItem['contractor']),
                    'has_builder' => isset($firstItem['builder']),
                ]);
            }

            // Обрабатываем каждый результат подрядчиков
            $processedResults = [];
            foreach ($results as $item) {
                // Обрабатываем изображения
                $images = [];
                if (isset($item['images']) && is_array($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (isset($img['path']) && isset($img['file_name'])) {
                            $path = rtrim($img['path'], '/');
                            $path = ltrim($path, '/');
                            $fileName = $img['file_name'];
                            $images[] = [
                                'thumbnail' => "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}",
                                'full' => "https://selcdn.trendagent.ru/images/{$path}/{$fileName}",
                                'path' => $img['path'],
                                'file_name' => $img['file_name'],
                            ];
                        }
                    }
                }

                // Обрабатываем поле name - может быть строкой или объектом
                $name = null;
                if (isset($item['name'])) {
                    if (is_string($item['name'])) {
                        $name = $item['name'];
                    } elseif (is_array($item['name'])) {
                        // Если name - объект вида {"label": "Проект", "value": "ДК-172"}
                        $name = $item['name']['value'] ?? $item['name']['label'] ?? null;
                        // Если не нашли value или label, ищем первое строковое значение
                        if (!$name) {
                            foreach ($item['name'] as $key => $val) {
                                if (is_string($val) && !empty($val)) {
                                    $name = $val;
                                    break;
                                }
                            }
                        }
                    }
                }
                
                // Обрабатываем цены
                $minPrice = null;
                $maxPrice = null;
                $minPrices = [];
                if (isset($item['price']) || isset($item['prices'])) {
                    if (isset($item['prices']) && is_array($item['prices'])) {
                        $priceValues = [];
                        foreach ($item['prices'] as $price) {
                            if (isset($price['value']) && is_numeric($price['value'])) {
                                $priceValues[] = (float)$price['value'];
                                $minPrices[] = [
                                    'label' => $price['label'] ?? 'Цена',
                                    'value' => $price['value'],
                                    'price' => (float)$price['value'],
                                    'unit' => $price['unit'] ?? '₽',
                                ];
                            }
                        }
                        if (!empty($priceValues)) {
                            $minPrice = min($priceValues);
                            $maxPrice = max($priceValues);
                        }
                    } elseif (isset($item['price'])) {
                        $minPrice = is_numeric($item['price']) ? (float)$item['price'] : null;
                        $maxPrice = $minPrice;
                    }
                }
                
                // Обрабатываем площади
                $areaTotal = $item['area_total'] ?? $item['total_area'] ?? $item['area'] ?? null;
                $areaLiving = $item['area_living'] ?? $item['living_area'] ?? null;
                $areaKitchen = $item['area_kitchen'] ?? $item['kitchen_area'] ?? null;
                $areaTerrace = $item['area_terrace'] ?? $item['terrace_area'] ?? null;
                
                // Обрабатываем параметры дома
                $floors = $item['floors'] ?? $item['floor'] ?? null;
                $bedrooms = $item['bedrooms'] ?? $item['rooms'] ?? null;
                $bathrooms = $item['bathrooms'] ?? $item['wc'] ?? null;
                
                // Обрабатываем отделку
                $finishing = $item['finishing'] ?? null;
                if (is_array($finishing)) {
                    $finishing = implode(', ', array_filter($finishing));
                }
                
                // Обрабатываем срок строительства
                $constructionTime = $item['construction_time'] ?? $item['deadline'] ?? null;
                if (is_array($constructionTime)) {
                    $constructionTime = $constructionTime['value'] ?? $constructionTime['label'] ?? null;
                }
                
                // Обрабатываем технологию строительства
                $technology = $item['technology'] ?? $item['building_technology'] ?? null;
                if (is_array($technology)) {
                    $technology = $technology['value'] ?? $technology['label'] ?? null;
                }
                
                // Обрабатываем подрядчика
                $contractor = $item['contractor'] ?? $item['builder'] ?? null;
                if (is_array($contractor)) {
                    $contractor = $contractor['name'] ?? $contractor['value'] ?? $contractor['label'] ?? null;
                }
                
                $processedResults[] = [
                    'id' => $item['_id'] ?? $item['id'] ?? null,
                    'guid' => $item['guid'] ?? null,
                    'name' => $name,
                    'specialization' => $item['specialization'] ?? null,
                    'rating' => $item['rating'] ?? null,
                    'experience' => $item['experience'] ?? null,
                    'description' => $item['description'] ?? null,
                    'images' => $images,
                    'contact' => $item['contact'] ?? null,
                    // Дополнительные поля для карточки
                    'contractor' => $contractor,
                    'construction_time' => $constructionTime,
                    'technology' => $technology,
                    'floors' => $floors,
                    'bedrooms' => $bedrooms,
                    'bathrooms' => $bathrooms,
                    'finishing' => $finishing,
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                    'min_prices' => $minPrices,
                    'area_total' => $areaTotal,
                    'area_living' => $areaLiving,
                    'area_kitchen' => $areaKitchen,
                    'area_terrace' => $areaTerrace,
                    // Сохраняем все исходные данные для детальной страницы
                    '_raw' => $item,
                ];
            }

            // Логируем структуру первого объекта для отладки
            if (!empty($processedResults)) {
                $firstObject = $processedResults[0];
                Log::info('Структура первого подрядчика из API', [
                    'object_keys' => array_keys($firstObject),
                    'has_contractor' => isset($firstObject['contractor']),
                    'has_construction_time' => isset($firstObject['construction_time']),
                    'has_technology' => isset($firstObject['technology']),
                    'has_prices' => isset($firstObject['min_price']) || isset($firstObject['min_prices']),
                    'has_areas' => isset($firstObject['area_total']) || isset($firstObject['area_living']),
                ]);
            }
            
            Log::info('Данные подрядчиков получены', [
                'total_count' => $totalCount,
                'results_count' => count($processedResults),
            ]);

            return [
                'success' => true,
                'data' => $processedResults,
                'total' => count($processedResults),
                'total_count' => $totalCount,
                'source' => 'contractors_api',
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('Ошибка при запросе к API подрядчиков', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при запросе к API подрядчиков: ' . $e->getMessage());
        }
    }

    /**
     * Получение детальной информации о проекте подрядчика
     * 
     * @param string $projectIdOrGuid ID или GUID проекта
     * @return array Детальная информация о проекте
     * @throws \Exception
     */
    public function getContractorProjectDetails(string $projectIdOrGuid): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Определяем, это ID или GUID
            $isGuid = !preg_match('/^[a-f0-9]{24}$/i', $projectIdOrGuid);
            
            // Для проектов подрядчиков используем ID из списка, если передан GUID
            // Сначала пытаемся найти проект в списке по GUID, чтобы получить его ID
            $projectId = $projectIdOrGuid;
            
            if ($isGuid) {
                // Если это GUID, нужно найти ID проекта в списке
                try {
                    $searchResult = $this->getContractorsSearch([
                        'count' => 1000,
                        'offset' => 0,
                    ]);
                    
                    if ($searchResult['success'] && !empty($searchResult['data'])) {
                        foreach ($searchResult['data'] as $project) {
                            if (isset($project['guid']) && $project['guid'] === $projectIdOrGuid) {
                                $projectId = $project['_id'] ?? $project['id'] ?? $projectIdOrGuid;
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Не удалось найти ID проекта по GUID, используем GUID напрямую', [
                        'guid' => $projectIdOrGuid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Формируем URL API для детальной информации о проекте
            // Используем ID (24 символа hex) для запроса
            $apiUrl = "https://house-api.trendagent.ru/v1/projects/{$projectId}";
            
            $queryParams = [
                'auth_token' => $authToken,
                'lang' => 'ru',
            ];
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API contractor project details', [
                'url' => $apiUrl,
                'project_id_or_guid' => $projectIdOrGuid,
                'project_id' => $projectId,
                'is_guid' => $isGuid,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => 'https://spb.trendagent.ru',
                    'Referer' => 'https://spb.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API детальной информации проекта подрядчика', [
                    'status_code' => $statusCode,
                    'url' => $fullUrl,
                    'project_id_or_guid' => $projectIdOrGuid,
                    'project_id' => $projectId,
                    'response_body' => substr($body, 0, 1000),
                ]);
                
                // Если 404, возможно endpoint другой - пробуем использовать данные из списка
                if ($statusCode === 404) {
                    Log::warning('Endpoint для детальной информации проекта не найден, используем данные из списка');
                    try {
                        $searchResult = $this->getContractorsSearch([
                            'count' => 1000,
                            'offset' => 0,
                        ]);
                        
                        if ($searchResult['success'] && !empty($searchResult['data'])) {
                            foreach ($searchResult['data'] as $project) {
                                if ((isset($project['guid']) && $project['guid'] === $projectIdOrGuid) ||
                                    (isset($project['_id']) && $project['_id'] === $projectIdOrGuid) ||
                                    (isset($project['id']) && $project['id'] === $projectIdOrGuid)) {
                                    // Используем данные из списка как детальную информацию
                                    return [
                                        'success' => true,
                                        'block_id' => $project['_id'] ?? $project['id'] ?? null,
                                        'block_guid' => $project['guid'] ?? $projectIdOrGuid,
                                        'data' => [
                                            'unified' => [
                                                'data' => $project,
                                            ],
                                        ],
                                        'raw_response' => $project,
                                        'source' => 'search_list',
                                    ];
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Не удалось получить данные из списка', ['error' => $e->getMessage()]);
                    }
                }
                
                throw new \Exception("Ошибка при запросе к API проекта подрядчика: HTTP {$statusCode}");
            }

            // Декодируем JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Обрабатываем данные проекта подрядчика
            $processedData = $data;
            
            // Если данные приходят в структуре data, извлекаем их
            if (isset($data['data']) && is_array($data['data'])) {
                $processedData = $data['data'];
            }
            
            // Обрабатываем поле name, если оно объект
            if (isset($processedData['name']) && is_array($processedData['name'])) {
                $processedData['name'] = $processedData['name']['value'] ?? $processedData['name']['label'] ?? null;
            }
            
            // Обрабатываем изображения
            $images = [];
            if (isset($processedData['images']) && is_array($processedData['images'])) {
                foreach ($processedData['images'] as $img) {
                    if (isset($img['path']) && isset($img['file_name'])) {
                        $path = rtrim($img['path'], '/');
                        $path = ltrim($path, '/');
                        $fileName = $img['file_name'];
                        $images[] = [
                            'thumbnail' => "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}",
                            'full' => "https://selcdn.trendagent.ru/images/{$path}/{$fileName}",
                            'path' => $img['path'],
                            'file_name' => $img['file_name'],
                        ];
                    }
                }
                $processedData['images'] = $images;
            }
            
            Log::info('Детальная информация о проекте подрядчика получена', [
                'project_id_or_guid' => $projectIdOrGuid,
                'data_keys' => array_keys($processedData),
                'has_name' => isset($processedData['name']),
                'has_images' => !empty($images),
            ]);

            return [
                'success' => true,
                'block_id' => $processedData['_id'] ?? $processedData['id'] ?? null,
                'block_guid' => $processedData['guid'] ?? $projectIdOrGuid,
                'data' => [
                    'unified' => [
                        'data' => $processedData,
                    ],
                ],
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('Ошибка при запросе к API детальной информации проекта подрядчика', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при запросе к API проекта подрядчика: ' . $e->getMessage());
        }
    }

    /**
     * Получение ID блока по GUID
     * 
     * @param string $guid GUID блока (например, 'villa-marina')
     * @param array $params Дополнительные параметры (city, lang)
     * @return array Массив с данными блока
     * @throws \Exception
     */
    public function getBlockById(string $guid, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            // Для поселков (villages) используем другой endpoint
            // Проверяем, является ли это GUID поселка (обычно это строки типа "lebyazhe")
            $isGuid = !preg_match('/^[a-f0-9]{24}$/i', $guid);
            
            // Для поселков используем стандартный endpoint api.trendagent.ru
            // Он должен работать и для поселков, так как они тоже являются блоками
            $apiUrl = 'https://api.trendagent.ru/v4_29/blocks/search/id/';
            $fullUrl = $apiUrl . '?' . http_build_query(array_merge($queryParams, ['guid' => $guid]));
            
            Log::info('getBlockById - запрос блока', [
                'guid' => $guid,
                'url' => $fullUrl,
                'is_guid_format' => $isGuid,
            ]);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            // Логирование для отладки
            if (empty($data['data'])) {
                Log::warning('getBlockById - блок не найден', [
                    'guid' => $guid,
                    'status_code' => $statusCode,
                    'response_keys' => array_keys($data ?? []),
                    'response_sample' => substr($body, 0, 500),
                ]);
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? null,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('getBlockById - ошибка Guzzle', [
                'guid' => $guid,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Ошибка при получении блока: ' . $e->getMessage());
        }
    }

    /**
     * Получение полной информации о блоке (unified)
     * 
     * @param string $blockId ID блока
     * @param array $params Дополнительные параметры
     * @return array Полная информация о блоке
     * @throws \Exception
     */
    public function getBlockUnified(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'ch' => 'false',
                'formating' => 'true',
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = "https://api.trendagent.ru/v4_29/blocks/{$blockId}/unified/";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            // Логирование для отладки поселков
            $unifiedData = $data['data'] ?? $data;
            if (is_array($unifiedData)) {
                Log::info('getBlockUnified - получены данные', [
                    'block_id' => $blockId,
                    'has_name' => isset($unifiedData['name']),
                    'has_min_prices' => isset($unifiedData['min_prices']),
                    'min_prices_count' => is_array($unifiedData['min_prices'] ?? null) ? count($unifiedData['min_prices']) : 0,
                    'has_plots_count' => isset($unifiedData['plots_count']),
                    'keys' => array_keys($unifiedData),
                ]);
            }

            return [
                'success' => true,
                'data' => $unifiedData,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении unified данных: ' . $e->getMessage());
        }
    }

    /**
     * Получение зданий блока на карте
     * 
     * @param string $blockId ID блока
     * @param array $params Дополнительные параметры
     * @return array Данные о зданиях
     * @throws \Exception
     */
    public function getBlockBuildings(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = "https://api.trendagent.ru/v4_29/blocks/{$blockId}/geo/buildings/";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении зданий: ' . $e->getMessage());
        }
    }

    /**
     * Получение квартир блока
     * 
     * @param string $blockId ID блока
     * @param array $params Параметры фильтрации (onrequest, room, price и т.д.)
     * @return array Данные о квартирах
     * @throws \Exception
     */
    public function getBlockApartments(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        // Генерируем ключ кэша
        $cacheKey = $this->getCacheKey('block_apartments', [
            'block_id' => $blockId,
            'params' => $params,
        ]);
        
        // Пытаемся получить данные из кэша (60 минут)
        return Cache::remember($cacheKey, 60 * 60, function () use ($blockId, $params) {
            try {
                $authToken = $this->getAuthToken();
                if (empty($authToken)) {
                    throw new \Exception('Токен авторизации не найден');
                }

                $defaultParams = [
                    'city' => '58c665588b6aa52311afa01b',
                    'lang' => 'ru',
                ];
                
                // Если передан onrequest, добавляем его в параметры
                // Также добавляем параметры, которые использует сайт донора для получения всех квартир
                if (isset($params['onrequest'])) {
                    // Преобразуем boolean в строку для API
                    if ($params['onrequest'] === true || $params['onrequest'] === 'true' || $params['onrequest'] === '1') {
                        $defaultParams['onrequest'] = 'true';
                    } elseif ($params['onrequest'] === false || $params['onrequest'] === 'false' || $params['onrequest'] === '0') {
                        $defaultParams['onrequest'] = 'false';
                    } else {
                        $defaultParams['onrequest'] = (string)$params['onrequest'];
                    }
                }
                
                $queryParams = array_merge($defaultParams, $params);
                // Убираем onrequest из queryParams, если он уже добавлен в defaultParams
                if (isset($defaultParams['onrequest'])) {
                    $queryParams['onrequest'] = $defaultParams['onrequest'];
                }
                $queryParams['auth_token'] = $authToken;

                $apiUrl = "https://api.trendagent.ru/v4_29/apartments/block/{$blockId}/search/";
                $fullUrl = $apiUrl . '?' . http_build_query($queryParams);
                
                Log::info('getBlockApartments - URL запроса', [
                    'block_id' => $blockId,
                    'onrequest_param' => $params['onrequest'] ?? 'not_set',
                    'onrequest_in_query' => $queryParams['onrequest'] ?? 'not_set',
                    'url' => $apiUrl,
                    'full_url_preview' => substr($fullUrl, 0, 200) . '...',
                ]);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            // Структура ответа API:
            // data.data.results - массив объектов с ключами типа "1#4 кв. 2024", содержащий массивы групп квартир
            // Каждая группа имеет массив "apartments" с квартирами
            // data.data.apartmentsCount - общее количество
            // data.data.onRequestApartmentsCount - количество под запрос
            // data.data.bookedApartmentsCount - забронированные
            $apiData = $data['data'] ?? $data;
            
            // Извлекаем квартиры из группированной структуры
            // Сохраняем группировку для отображения в таблице (важно для домов)
            $results = [];
            $groupedResults = []; // Сохраняем группированную структуру
            
            // Проверяем наличие results
            if (isset($apiData['results'])) {
                $resultsData = $apiData['results'];
                
                // results может быть объектом с ключами типа "1#4 кв. 2024" или массивом
                if (is_array($resultsData)) {
                    // Обрабатываем results как объект/массив
                    foreach ($resultsData as $key => $groupsArray) {
                        // Пропускаем числовые ключи, если это не массив групп
                        if (is_array($groupsArray)) {
                            // $groupsArray - массив групп
                            $groupedResults[$key] = [];
                            foreach ($groupsArray as $group) {
                                if (isset($group['apartments']) && is_array($group['apartments'])) {
                                    // Сохраняем группу с квартирами
                                    $groupedResults[$key][] = $group;
                                    // Добавляем все квартиры из группы в плоский массив
                                    foreach ($group['apartments'] as $apartment) {
                                        $results[] = $apartment;
                                    }
                                } elseif (isset($group['_id']) || isset($group['number'])) {
                                    // Если элемент сам является квартирой (не группа)
                                    $results[] = $group;
                                }
                            }
                        }
                    }
                }
            } else {
                // Если нет results, ищем квартиры напрямую в структуре
                // Структура: { "1#4 кв. 2024": [{ apartments: [...] }, { apartments: [...] }, ...], ... }
                foreach ($apiData as $key => $value) {
                    // Пропускаем служебные поля
                    if (in_array($key, ['apartmentsCount', 'onRequestApartmentsCount', 'bookedApartmentsCount', 'total', 'count', 'results'])) {
                        continue;
                    }
                    
                    if (is_array($value)) {
                        // $value - массив групп
                        $groupedResults[$key] = [];
                        foreach ($value as $group) {
                            if (isset($group['apartments']) && is_array($group['apartments'])) {
                                // Сохраняем группу с квартирами
                                $groupedResults[$key][] = $group;
                                // Добавляем все квартиры из группы в плоский массив
                                foreach ($group['apartments'] as $apartment) {
                                    $results[] = $apartment;
                                }
                            } elseif (isset($group['_id']) || isset($group['number'])) {
                                // Если элемент сам является квартирой (не группа)
                                $results[] = $group;
                            }
                        }
                    }
                }
            }
            
            $total = $apiData['apartmentsCount'] ?? $apiData['total'] ?? count($results);
            $onRequestCount = $apiData['onRequestApartmentsCount'] ?? 0;
            $bookedCount = $apiData['bookedApartmentsCount'] ?? 0;
            
            Log::info('getBlockApartments - извлечение квартир', [
                'total_from_api' => $total,
                'results_count' => count($results),
                'grouped_results_count' => count($groupedResults),
                'on_request_count' => $onRequestCount,
                'booked_count' => $bookedCount,
                'api_data_keys' => array_keys($apiData),
            ]);

            return [
                'success' => true,
                'data' => $results, // Плоский массив квартир
                'grouped_data' => $groupedResults, // Группированная структура (для таблицы домов)
                    'total' => $total,
                    'count' => count($results),
                    'apartments_count' => $total,
                    'on_request_count' => $onRequestCount,
                    'booked_count' => $bookedCount,
                    'raw_response' => $data,
                ];
            } catch (GuzzleException $e) {
                throw new \Exception('Ошибка при получении квартир: ' . $e->getMessage());
            }
        });
    }

    /**
     * Получение мест парковки для блока
     * 
     * @param string $blockId ID блока
     * @param array $params Дополнительные параметры
     * @return array Данные о местах парковки
     * @throws \Exception
     */
    public function getBlockParkings(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = "https://parkings.trendagent.ru/parkings/block/{$blockId}";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            Log::info('getBlockParkings - запрос к API', [
                'url' => $apiUrl,
                'block_id' => $blockId,
            ]);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            Log::info('getBlockParkings - получен ответ', [
                'block_id' => $blockId,
                'data_keys' => is_array($data) ? array_keys($data) : 'not_array',
                'has_data' => !empty($data),
            ]);

            // Структура ответа API может быть разной, обрабатываем различные варианты
            $apiData = $data['data'] ?? $data;
            
            // Возвращаем данные как есть, обработка будет на фронтенде
            return [
                'success' => true,
                'data' => $apiData,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('getBlockParkings - ошибка Guzzle', [
                'block_id' => $blockId,
                'message' => $e->getMessage(),
            ]);
            throw new \Exception('Ошибка при получении мест парковки: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('getBlockParkings - общая ошибка', [
                'block_id' => $blockId,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получение планировок блока
     * 
     * @param string $blockId ID блока
     * @param array $params Дополнительные параметры
     * @return array Данные о планировках
     * @throws \Exception
     */
    public function getBlockPlans(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        // Генерируем ключ кэша
        $cacheKey = $this->getCacheKey('block_plans', [
            'block_id' => $blockId,
            'params' => $params,
        ]);
        
        // Пытаемся получить данные из кэша (60 минут)
        return Cache::remember($cacheKey, 60 * 60, function () use ($blockId, $params) {
            try {
                $authToken = $this->getAuthToken();
                if (empty($authToken)) {
                    throw new \Exception('Токен авторизации не найден');
                }

                $defaultParams = [
                    'cache' => 'false',
                    'formating' => 'true',
                    'city' => '58c665588b6aa52311afa01b',
                    'lang' => 'ru',
                ];
                $queryParams = array_merge($defaultParams, $params);
                $queryParams['auth_token'] = $authToken;

                $apiUrl = "https://api.trendagent.ru/v4_29/media/block/{$blockId}/plans/";
                $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

                $response = $this->client->get($fullUrl, [
                    'headers' => $this->getAuthHeaders(),
                    'timeout' => 30,
                    'verify' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                if ($statusCode !== 200) {
                    throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
                }

                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
                }

                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data,
                    'raw_response' => $data,
                ];
            } catch (GuzzleException $e) {
                throw new \Exception('Ошибка при получении планировок: ' . $e->getMessage());
            }
        });
    }

    /**
     * Получение хода строительства блока
     * 
     * @param string $blockId ID блока
     * @param string|null $year Год (если null, возвращает список годов)
     * @param array $params Дополнительные параметры
     * @return array Данные о ходе строительства
     * @throws \Exception
     */
    public function getBlockProgress(string $blockId, ?string $year = null, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'cache' => 'false',
                'formating' => 'true',
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            if ($year === null) {
                $apiUrl = "https://api.trendagent.ru/v4_29/media/block/{$blockId}/progress/years/";
            } else {
                $apiUrl = "https://api.trendagent.ru/v4_29/media/block/{$blockId}/progress/{$year}/";
            }
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении хода строительства: ' . $e->getMessage());
        }
    }

    /**
     * Получение отделок блока
     * 
     * @param string $blockId ID блока
     * @param array $params Дополнительные параметры
     * @return array Данные об отделках
     * @throws \Exception
     */
    public function getBlockFinishings(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = "https://api.trendagent.ru/v4_29/finishings/block/{$blockId}/";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении отделок: ' . $e->getMessage());
        }
    }

    /**
     * Получение преимуществ блока
     * 
     * @param string $blockId ID блока
     * @param array $params Дополнительные параметры
     * @return array Данные о преимуществах
     * @throws \Exception
     */
    public function getBlockAdvantages(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = "https://api.trendagent.ru/v4_29/blocks/{$blockId}/advantages/";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении преимуществ: ' . $e->getMessage());
        }
    }

    /**
     * Получение мест рядом с блоком
     * 
     * @param string $blockId ID блока
     * @param array $params Дополнительные параметры
     * @return array Данные о местах рядом
     * @throws \Exception
     */
    public function getBlockNearbyPlaces(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = "https://api.trendagent.ru/v4_29/blocks/{$blockId}/nearby_places/";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении мест рядом: ' . $e->getMessage());
        }
    }

    /**
     * Получение минимальной цены квартир блока
     * 
     * @param string $blockId ID блока
     * @param array $params Параметры (onrequest, reservation)
     * @return array Данные о минимальной цене
     * @throws \Exception
     */
    public function getBlockMinPrice(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = "https://api.trendagent.ru/v4_29/blocks/{$blockId}/apartments/min-price/";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении минимальной цены: ' . $e->getMessage());
        }
    }

    /**
     * Получение видео блока
     * 
     * @param string $blockId ID блока
     * @param array $params Дополнительные параметры
     * @return array Данные о видео
     * @throws \Exception
     */
    public function getBlockVideos(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = "https://video.trendagent.ru/videos/block/{$blockId}";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении видео: ' . $e->getMessage());
        }
    }

    /**
     * Получение файлов блока
     * 
     * @param string $blockId ID блока
     * @param array $params Дополнительные параметры
     * @return array Данные о файлах
     * @throws \Exception
     */
    public function getBlockFiles(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = "https://files.trendagent.ru/fs/list/block/{$blockId}";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении файлов: ' . $e->getMessage());
        }
    }

    /**
     * Получение всех данных блока (комплексный метод)
     * 
     * @param string $blockIdOrGuid ID или GUID блока
     * @param array $options Опции загрузки (какие данные загружать)
     * @return array Все данные блока
     * @throws \Exception
     */
    public function getBlockFullData(string $blockIdOrGuid, array $options = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        $defaultOptions = [
            'unified' => true,
            'buildings' => true,
            'apartments' => true,
            'plans' => true,
            'progress' => true,
            'finishings' => true,
            'advantages' => true,
            'nearby_places' => true,
            'min_price' => true,
            'videos' => true,
            'files' => true,
            'rewards' => false,
            'discounts' => false,
            'mortgage' => false,
            'installments' => false,
            'banks' => false,
            'contacts' => false,
            '3d_tour' => false,
        ];
        $options = array_merge($defaultOptions, $options);

        // Генерируем ключ кэша
        $cacheKey = $this->getCacheKey('block_full_data', [
            'block_id_or_guid' => $blockIdOrGuid,
            'options' => $options,
        ]);
        
        // Пытаемся получить данные из кэша (60 минут)
        return Cache::remember($cacheKey, 60 * 60, function () use ($blockIdOrGuid, $options) {
            $result = [
                'success' => true,
                'block_id' => null,
                'block_guid' => null,
                'data' => [],
            ];

            try {
            // Определяем, это ID или GUID
            $isGuid = !preg_match('/^[a-f0-9]{24}$/i', $blockIdOrGuid);
            
            if ($isGuid) {
                // Получаем ID по GUID
                $blockData = $this->getBlockById($blockIdOrGuid);
                $blockId = $blockData['data']['_id'] ?? null;
                $result['block_guid'] = $blockIdOrGuid;
                if (!$blockId) {
                    throw new \Exception('Блок с GUID ' . $blockIdOrGuid . ' не найден');
                }
            } else {
                $blockId = $blockIdOrGuid;
            }
            
            $result['block_id'] = $blockId;

            // Загружаем данные параллельно
            if ($options['unified']) {
                try {
                    $result['data']['unified'] = $this->getBlockUnified($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки unified данных', ['error' => $e->getMessage()]);
                    $result['data']['unified'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['buildings']) {
                try {
                    $result['data']['buildings'] = $this->getBlockBuildings($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки buildings', ['error' => $e->getMessage()]);
                    $result['data']['buildings'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['apartments']) {
                try {
                    // Параметры для загрузки квартир
                    $apartmentsParams = [];
                    if (isset($options['apartments_params']) && is_array($options['apartments_params'])) {
                        $apartmentsParams = $options['apartments_params'];
                    }
                    
                    // Для домов добавляем фильтр room=[30, 40] (коттеджи и таунхаусы)
                    if (isset($options['object_type']) && $options['object_type'] === 'houses') {
                        $apartmentsParams['room'] = [30, 40];
                    }
                    
                    // НЕ устанавливаем onrequest по умолчанию - показываем ВСЕ доступные квартиры
                    // onrequest будет использоваться только если явно указан в запросе
                    
                    Log::info('getBlockFullData - запрос getBlockApartments', [
                        'block_id' => $blockId,
                        'apartments_params' => $apartmentsParams,
                        'object_type' => $options['object_type'] ?? null,
                    ]);
                    
                    $apartmentsResult = $this->getBlockApartments($blockId, $apartmentsParams);
                    
                    Log::info('getBlockFullData - получены apartments', [
                        'block_id' => $blockId,
                        'success' => $apartmentsResult['success'] ?? false,
                        'count' => $apartmentsResult['count'] ?? 0,
                        'total' => $apartmentsResult['total'] ?? 0,
                        'data_count' => is_array($apartmentsResult['data'] ?? []) ? count($apartmentsResult['data']) : 0,
                    ]);
                    
                    $result['data']['apartments'] = $apartmentsResult;
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки apartments', ['error' => $e->getMessage()]);
                    $result['data']['apartments'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['plans']) {
                try {
                    $result['data']['plans'] = $this->getBlockPlans($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки plans', ['error' => $e->getMessage()]);
                    $result['data']['plans'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['progress']) {
                try {
                    $result['data']['progress'] = $this->getBlockProgress($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки progress', ['error' => $e->getMessage()]);
                    $result['data']['progress'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['finishings']) {
                try {
                    $result['data']['finishings'] = $this->getBlockFinishings($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки finishings', ['error' => $e->getMessage()]);
                    $result['data']['finishings'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['advantages']) {
                try {
                    $result['data']['advantages'] = $this->getBlockAdvantages($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки advantages', ['error' => $e->getMessage()]);
                    $result['data']['advantages'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['nearby_places']) {
                try {
                    $result['data']['nearby_places'] = $this->getBlockNearbyPlaces($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки nearby_places', ['error' => $e->getMessage()]);
                    $result['data']['nearby_places'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['min_price']) {
                try {
                    $result['data']['min_price'] = $this->getBlockMinPrice($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки min_price', ['error' => $e->getMessage()]);
                    $result['data']['min_price'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['videos']) {
                try {
                    $result['data']['videos'] = $this->getBlockVideos($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки videos', ['error' => $e->getMessage()]);
                    $result['data']['videos'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['files']) {
                try {
                    $result['data']['files'] = $this->getBlockFiles($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки files', ['error' => $e->getMessage()]);
                    $result['data']['files'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['rewards']) {
                try {
                    $builderIdRaw = $result['data']['unified']['data']['builder_id'] ?? $result['data']['unified']['data']['builder'] ?? null;
                    // Преобразуем builderId в строку, если это массив, берем _id или первый элемент
                    $builderId = null;
                    if ($builderIdRaw) {
                        if (is_array($builderIdRaw)) {
                            $builderId = $builderIdRaw['_id'] ?? $builderIdRaw['id'] ?? (is_array($builderIdRaw) && count($builderIdRaw) > 0 ? (string)($builderIdRaw[0] ?? reset($builderIdRaw)) : null);
                        } else {
                            $builderId = (string)$builderIdRaw;
                        }
                    }
                    $result['data']['rewards'] = $this->getRewards($blockId, $builderId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки rewards', ['error' => $e->getMessage()]);
                    $result['data']['rewards'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['discounts']) {
                try {
                    $builderIdRaw = $result['data']['unified']['data']['builder_id'] ?? $result['data']['unified']['data']['builder'] ?? null;
                    // Преобразуем builderId в строку, если это массив, берем _id или первый элемент
                    $builderId = null;
                    if ($builderIdRaw) {
                        if (is_array($builderIdRaw)) {
                            $builderId = $builderIdRaw['_id'] ?? $builderIdRaw['id'] ?? (is_array($builderIdRaw) && count($builderIdRaw) > 0 ? (string)($builderIdRaw[0] ?? reset($builderIdRaw)) : null);
                        } else {
                            $builderId = (string)$builderIdRaw;
                        }
                    }
                    $result['data']['discounts'] = $this->getDiscounts($blockId, $builderId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки discounts', ['error' => $e->getMessage()]);
                    $result['data']['discounts'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['mortgage']) {
                try {
                    $result['data']['mortgage'] = $this->getMortgage($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки mortgage', ['error' => $e->getMessage()]);
                    $result['data']['mortgage'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['installments']) {
                try {
                    $result['data']['installments'] = $this->getInstallments($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки installments', ['error' => $e->getMessage()]);
                    $result['data']['installments'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['banks']) {
                try {
                    $result['data']['banks'] = $this->getBanks($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки banks', ['error' => $e->getMessage()]);
                    $result['data']['banks'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['contacts']) {
                try {
                    $result['data']['contacts'] = $this->getContacts($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки contacts', ['error' => $e->getMessage()]);
                    $result['data']['contacts'] = ['error' => $e->getMessage()];
                }
            }

            if ($options['3d_tour']) {
                try {
                    $result['data']['tour_3d'] = $this->get3DTour($blockId);
                } catch (\Exception $e) {
                    Log::warning('Ошибка загрузки 3d_tour', ['error' => $e->getMessage()]);
                    $result['data']['tour_3d'] = ['error' => $e->getMessage()];
                }
            }

                return $result;
            } catch (\Exception $e) {
                Log::error('Ошибка при получении полных данных блока', [
                    'block_id_or_guid' => $blockIdOrGuid,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Получение списка корпусов для шахматки
     * 
     * @param string $blockId ID блока
     * @param array $params Параметры запроса (room, city, lang)
     * @return array Список корпусов с количеством квартир
     * @throws \Exception
     */
    public function getCheckerboardBuildings(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        // Генерируем ключ кэша
        $cacheKey = $this->getCacheKey('checkerboard_buildings', [
            'block_id' => $blockId,
            'params' => $params,
        ]);
        
        // Пытаемся получить данные из кэша (60 минут)
        return Cache::remember($cacheKey, 60 * 60, function () use ($blockId, $params) {
            try {
                $authToken = $this->getAuthToken();
                if (empty($authToken)) {
                    throw new \Exception('Токен авторизации не найден');
                }

                $defaultParams = [
                    'city' => '58c665588b6aa52311afa01b',
                    'lang' => 'ru',
                ];
                $queryParams = array_merge($defaultParams, $params);
                
                // Обрабатываем параметр room для правильного формирования URL
                $roomParams = [];
                if (isset($queryParams['room']) && is_array($queryParams['room'])) {
                    $roomParams = $queryParams['room'];
                    unset($queryParams['room']);
                } elseif (isset($queryParams['room'])) {
                    $roomParams = [$queryParams['room']];
                    unset($queryParams['room']);
                }
                
                // Обрабатываем параметр onrequest (для фильтрации квартир под запрос)
                $onRequestParam = null;
                if (isset($queryParams['onrequest'])) {
                    $onRequestParam = $queryParams['onrequest'];
                    unset($queryParams['onrequest']);
                }
                
                $queryParams['auth_token'] = $authToken;
                
                // Формируем базовый query string
                $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
                
                // Добавляем параметры room отдельно (room=30&room=40)
                if (!empty($roomParams)) {
                    $roomQuery = [];
                    foreach ($roomParams as $room) {
                        $roomQuery[] = 'room=' . urlencode($room);
                    }
                    if (!empty($queryString)) {
                        $queryString .= '&' . implode('&', $roomQuery);
                    } else {
                        $queryString = implode('&', $roomQuery);
                    }
                }
                
                // Добавляем параметр onrequest, если указан
                if ($onRequestParam !== null) {
                    $onRequestValue = $onRequestParam === true || $onRequestParam === 'true' || $onRequestParam === '1' ? 'true' : 'false';
                    if (!empty($queryString)) {
                        $queryString .= '&onrequest=' . urlencode($onRequestValue);
                    } else {
                        $queryString = 'onrequest=' . urlencode($onRequestValue);
                    }
                }
                
                $apiUrl = "https://api.trendagent.ru/v4_29/checkerboards/{$blockId}/apartments/buildings/";
                $fullUrl = $apiUrl . '?' . $queryString;

                Log::info('Запрос к API checkerboard buildings', [
                    'block_id' => $blockId,
                    'url' => $apiUrl,
                    'room_params' => $roomParams,
                ]);

                $response = $this->client->get($fullUrl, [
                    'headers' => $this->getAuthHeaders(),
                    'timeout' => 30,
                    'verify' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                if ($statusCode !== 200) {
                    throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
                }

                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
                }

                // Проверяем структуру ответа - может быть data.results или просто data
                $buildingsList = [];
                if (isset($data['data']) && is_array($data['data'])) {
                    if (isset($data['data']['results']) && is_array($data['data']['results'])) {
                        // Новая структура: объект с results внутри data
                        $buildingsList = $data['data']['results'];
                    } else {
                        // Старая структура: массив зданий в data
                        $buildingsList = $data['data'];
                    }
                } elseif (isset($data['results']) && is_array($data['results'])) {
                    // Альтернативная структура: results на верхнем уровне
                    $buildingsList = $data['results'];
                } else {
                    $buildingsList = $data['data'] ?? $data;
                }

                return [
                    'success' => true,
                    'data' => $buildingsList,
                    'raw_response' => $data,
                ];
            } catch (GuzzleException $e) {
                throw new \Exception('Ошибка при получении корпусов для шахматки: ' . $e->getMessage());
            }
        });
    }

    /**
     * Получение квартир по этажам для выбранного корпуса (шахматка)
     * 
     * @param string $blockId ID блока
     * @param string $buildingId ID корпуса
     * @param array $params Дополнительные параметры
     * @return array Квартиры по этажам/секциям
     * @throws \Exception
     */
    public function getCheckerboardApartments(string $blockId, string $buildingId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        // Генерируем ключ кэша
        $cacheKey = $this->getCacheKey('checkerboard_apartments', [
            'block_id' => $blockId,
            'building_id' => $buildingId,
            'params' => $params,
        ]);
        
        // Пытаемся получить данные из кэша (60 минут)
        return Cache::remember($cacheKey, 60 * 60, function () use ($blockId, $buildingId, $params) {
            try {
                $authToken = $this->getAuthToken();
                if (empty($authToken)) {
                    throw new \Exception('Токен авторизации не найден');
                }

                $defaultParams = [
                    'city' => '58c665588b6aa52311afa01b',
                    'lang' => 'ru',
                    'building_id' => $buildingId,
                ];
                
                // Убираем onrequest из запроса квартир - он фильтрует неправильно
                // Параметр onrequest используется только для фильтрации зданий
                $filteredParams = $params;
                unset($filteredParams['onrequest']);
                
                $queryParams = array_merge($defaultParams, $filteredParams);
                $queryParams['auth_token'] = $authToken;

                $apiUrl = "https://api.trendagent.ru/v4_29/checkerboards/{$blockId}/apartments/";
                $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

                Log::info('Запрос к API checkerboard apartments', [
                    'block_id' => $blockId,
                    'building_id' => $buildingId,
                    'url' => $apiUrl,
                ]);

                $response = $this->client->get($fullUrl, [
                    'headers' => $this->getAuthHeaders(),
                    'timeout' => 30,
                    'verify' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                if ($statusCode !== 200) {
                    throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
                }

                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
                }

                // Проверяем структуру ответа - может быть data.results или просто data
                $apartmentsData = null;
                if (isset($data['data']['results']) && is_array($data['data']['results'])) {
                    // Новая структура: объект с results внутри data
                    $apartmentsData = $data['data']['results'];
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    // Старая структура: массив квартир в data
                    if (isset($data['data']['results']) && is_array($data['data']['results'])) {
                        $apartmentsData = $data['data']['results'];
                    } else {
                        $apartmentsData = $data['data'];
                    }
                } elseif (isset($data['results']) && is_array($data['results'])) {
                    // Альтернативная структура: results на верхнем уровне
                    $apartmentsData = $data['results'];
                } else {
                    $apartmentsData = $data['data'] ?? $data;
                }

                return [
                    'success' => true,
                    'data' => $apartmentsData,
                    'raw_response' => $data,
                ];
            } catch (GuzzleException $e) {
                throw new \Exception('Ошибка при получении квартир для шахматки: ' . $e->getMessage());
            }
        });
    }

    /**
     * Получение детальной информации о квартире
     * 
     * @param string $apartmentId ID квартиры
     * @param string|null $blockId ID блока (опционально)
     * @param array $params Дополнительные параметры
     * @return array Детальная информация о квартире
     * @throws \Exception
     */
    public function getApartmentDetail(string $apartmentId, ?string $blockId = null, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        // Генерируем ключ кэша
        $cacheKey = $this->getCacheKey('apartment_detail', [
            'apartment_id' => $apartmentId,
            'block_id' => $blockId,
            'params' => $params,
        ]);
        
        // Пытаемся получить данные из кэша (60 минут)
        return Cache::remember($cacheKey, 60 * 60, function () use ($apartmentId, $blockId, $params) {
            try {
                $authToken = $this->getAuthToken();
                if (empty($authToken)) {
                    throw new \Exception('Токен авторизации не найден');
                }

                // Параметры для API запроса (только те, что нужны для API, не фронтенд параметры)
                // Параметры sort, sort_order, open - это параметры фронтенда, не передаем их в API
                $apiParams = [];
                if (isset($params['city'])) {
                    $apiParams['city'] = $params['city'];
                }
                if (isset($params['lang'])) {
                    $apiParams['lang'] = $params['lang'];
                }
                
                $defaultParams = [
                    'city' => '58c665588b6aa52311afa01b',
                    'lang' => 'ru',
                ];
                $queryParams = array_merge($defaultParams, $apiParams);
                $queryParams['auth_token'] = $authToken;

                // Согласно документации TRENDAGENT_PAGE_STRUCTURE.md:
                // Правильный формат: GET /v4_29/apartments/block/{blockId}/apartment/{apartmentId}/
                // В документации указан слэш в конце, но он может вызывать проблемы с query параметрами
                // Пробуем сначала с blockId, если передан
                if ($blockId) {
                    // Формат: /v4_29/apartments/block/{blockId}/apartment/{apartmentId}/
                    // Пробуем БЕЗ слэша в конце, так как он добавляется перед query параметрами
                    $apiUrl = "https://api.trendagent.ru/v4_29/apartments/block/{$blockId}/apartment/{$apartmentId}";
                } else {
                    // Fallback: /v4_29/apartments/{apartmentId}/
                    $apiUrl = "https://api.trendagent.ru/v4_29/apartments/{$apartmentId}";
                }
                
                // НЕ убираем слэш - возможно, он нужен для правильной маршрутизации API
                // Но добавляем его только если его нет
                if (substr($apiUrl, -1) !== '/') {
                    $apiUrl .= '/';
                }
                
                // Формируем query string, убеждаясь что параметры правильно закодированы
                $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
                $fullUrl = $apiUrl . '?' . $queryString;

                Log::info('Запрос к API apartment detail', [
                    'apartment_id' => $apartmentId,
                    'block_id' => $blockId,
                    'url' => $apiUrl,
                    'full_url' => $fullUrl,
                    'query_params' => array_merge($queryParams, ['auth_token' => '***']), // Не логируем токен
                ]);

                $response = null;
                $statusCode = null;
                $body = null;
                $useFallback = false;

                try {
                    $response = $this->client->get($fullUrl, [
                        'headers' => $this->getAuthHeaders(),
                        'timeout' => 30,
                        'verify' => false,
                        'http_errors' => false, // Не выбрасывать исключение при 4xx/5xx
                    ]);

                    $statusCode = $response->getStatusCode();
                    $body = $response->getBody()->getContents();

                    // Если запрос с blockId не сработал (404 или 400 или 500), пробуем fallback
                    if ($blockId && ($statusCode === 404 || $statusCode === 400 || $statusCode === 500)) {
                        Log::warning('Запрос с blockId не сработал, включаем fallback', [
                            'apartment_id' => $apartmentId,
                            'block_id' => $blockId,
                            'original_status' => $statusCode,
                            'body_preview' => substr($body, 0, 200),
                        ]);
                        $useFallback = true;
                    } elseif ($statusCode === 200) {
                        // Если запрос успешен, парсим ответ и возвращаем
                        $data = json_decode($body, true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($data['data'])) {
                            return [
                                'success' => true,
                                'data' => $data['data'],
                                'raw_response' => $data,
                                'source' => 'direct_with_blockId',
                            ];
                        }
                    }
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    // Guzzle выбрасывает ClientException при 4xx статусах
                    $response = $e->hasResponse() ? $e->getResponse() : null;
                    $statusCode = $response ? $response->getStatusCode() : 0;
                    $body = $response ? $response->getBody()->getContents() : $e->getMessage();
                    
                    // Если запрос с blockId не сработал, пробуем fallback
                    if ($blockId && ($statusCode === 404 || $statusCode === 400 || $statusCode === 500)) {
                        Log::warning('Запрос с blockId не сработал, включаем fallback (из catch)', [
                            'apartment_id' => $apartmentId,
                            'original_status' => $statusCode,
                            'body_preview' => substr($body, 0, 200),
                        ]);
                        $useFallback = true;
                    } else {
                        // Если это не 404/400/500 или нет blockId, пробрасываем исключение дальше
                        throw $e;
                    }
                } catch (\Exception $e) {
                    // Обработка других исключений
                    Log::error('Ошибка при запросе apartment detail', [
                        'apartment_id' => $apartmentId,
                        'block_id' => $blockId,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Если есть blockId, пробуем fallback
                    if ($blockId) {
                        $useFallback = true;
                    } else {
                        throw $e;
                    }
                }

                // Выполняем fallback, если нужно
                if ($useFallback) {
                    Log::info('Начало выполнения fallback для apartment detail', [
                        'apartment_id' => $apartmentId,
                        'block_id' => $blockId,
                        'useFallback' => $useFallback,
                    ]);
                    
                    // Fallback 1: пробуем получить квартиру из списка квартир блока
                    if ($blockId) {
                        try {
                            Log::info('Попытка получить квартиру из списка блока (Fallback 1)', [
                                'apartment_id' => $apartmentId,
                                'block_id' => $blockId,
                            ]);
                            
                            // Получаем список квартир блока с большим лимитом, включая квартиры "по запросу"
                            $apartmentsData = $this->getBlockApartments($blockId, ['count' => 5000, 'offset' => 0, 'onrequest' => true]);
                            
                            Log::info('Fallback 1 - получены данные списка квартир', [
                                'apartment_id' => $apartmentId,
                                'has_data' => isset($apartmentsData['data']),
                                'data_type' => isset($apartmentsData['data']) ? gettype($apartmentsData['data']) : 'null',
                                'data_count' => isset($apartmentsData['data']) && is_array($apartmentsData['data']) ? count($apartmentsData['data']) : 0,
                            ]);
                            
                            // Ищем нужную квартиру в списке
                            if (isset($apartmentsData['data'])) {
                                $apartments = $apartmentsData['data'];
                                if (is_array($apartments)) {
                                    // Проверяем разные структуры данных
                                    $flatList = [];
                                    
                                    // Если есть grouped_data, извлекаем квартиры из групп
                                    if (isset($apartmentsData['grouped_data']) && is_array($apartmentsData['grouped_data'])) {
                                        foreach ($apartmentsData['grouped_data'] as $key => $groups) {
                                            if (is_array($groups)) {
                                                foreach ($groups as $group) {
                                                    if (isset($group['apartments']) && is_array($group['apartments'])) {
                                                        $flatList = array_merge($flatList, $group['apartments']);
                                                    } elseif (isset($group['_id']) || isset($group['id'])) {
                                                        // Если элемент сам является квартирой
                                                        $flatList[] = $group;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    // Если grouped_data пуст, используем прямой массив
                                    if (empty($flatList)) {
                                        $flatList = is_array($apartments) ? $apartments : [];
                                    }
                                    
                                    Log::info('Fallback 1 - извлечен список квартир', [
                                        'apartment_id' => $apartmentId,
                                        'flat_list_count' => count($flatList),
                                        'first_apt_id' => !empty($flatList) ? ($flatList[0]['_id'] ?? $flatList[0]['id'] ?? 'unknown') : 'empty',
                                    ]);
                                    
                                    // Ищем квартиру по ID (проверяем и _id и id)
                                    foreach ($flatList as $apt) {
                                        $aptId = $apt['_id'] ?? $apt['id'] ?? null;
                                        if ($aptId === $apartmentId || (string)$aptId === (string)$apartmentId) {
                                            Log::info('Квартира найдена в списке блока (Fallback 1)', [
                                                'apartment_id' => $apartmentId,
                                                'found_id' => $aptId,
                                            ]);
                                            return [
                                                'success' => true,
                                                'data' => $apt,
                                                'raw_response' => $apt,
                                                'source' => 'block_apartments_list',
                                            ];
                                        }
                                    }
                                    
                                    Log::warning('Квартира не найдена в списке блока (Fallback 1)', [
                                        'apartment_id' => $apartmentId,
                                        'searched_in_count' => count($flatList),
                                        'sample_ids' => array_slice(array_map(function($a) {
                                            return $a['_id'] ?? $a['id'] ?? 'no_id';
                                        }, $flatList), 0, 5),
                                    ]);
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Не удалось получить квартиру из списка блока (Fallback 1)', [
                                'apartment_id' => $apartmentId,
                                'block_id' => $blockId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                        }
                    }
                    
                    // Fallback 2: пробуем прямой запрос без blockId
                    $apiUrl = "https://api.trendagent.ru/v4_29/apartments/{$apartmentId}";
                    $apiUrl = rtrim($apiUrl, '/');
                    $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
                    $fullUrl = $apiUrl . '?' . $queryString;
                    
                    Log::info('Fallback URL для apartment detail', [
                        'apartment_id' => $apartmentId,
                        'url' => $apiUrl,
                        'full_url' => $fullUrl,
                    ]);
                    
                    try {
                        $response = $this->client->get($fullUrl, [
                            'headers' => $this->getAuthHeaders(),
                            'timeout' => 30,
                            'verify' => false,
                            'http_errors' => false,
                        ]);
                        
                        $statusCode = $response->getStatusCode();
                        $body = $response->getBody()->getContents();
                        
                        Log::info('Fallback запрос завершен', [
                            'apartment_id' => $apartmentId,
                            'status_code' => $statusCode,
                            'body_preview' => substr($body, 0, 200),
                        ]);
                        
                        // Если Fallback 2 вернул успешный ответ
                        if ($statusCode === 200) {
                            $data = json_decode($body, true);
                            if (json_last_error() === JSON_ERROR_NONE && isset($data['data'])) {
                                return [
                                    'success' => true,
                                    'data' => $data['data'],
                                    'raw_response' => $data,
                                    'source' => 'fallback_direct_apartment',
                                ];
                            }
                        }
                        
                        // Проверяем, является ли ошибка Location17124
                        $isLocation17124 = false;
                        if ($statusCode === 500 && strlen($body) > 0) {
                            $errorData = json_decode($body, true);
                            if (isset($errorData['errors']['codeName']) && $errorData['errors']['codeName'] === 'Location17124') {
                                $isLocation17124 = true;
                                Log::info('Fallback 2 вернул Location17124', [
                                    'apartment_id' => $apartmentId,
                                    'block_id' => $blockId,
                                ]);
                            }
                        }
                        
                        // Если Fallback 2 вернул 500 (особенно Location17124), пробуем получить из списка блока
                        if (($statusCode === 500 || $isLocation17124) && $blockId) {
                            Log::info('Fallback 2 вернул 500, пробуем получить из списка блока', [
                                'apartment_id' => $apartmentId,
                                'block_id' => $blockId,
                            ]);
                            
                            try {
                                // Получаем список квартир блока с большим лимитом, включая квартиры "по запросу"
                                $apartmentsData = $this->getBlockApartments($blockId, ['count' => 5000, 'offset' => 0, 'onrequest' => true]);
                                
                                // Ищем нужную квартиру в списке
                                if (isset($apartmentsData['data'])) {
                                    $apartments = $apartmentsData['data'];
                                    if (is_array($apartments)) {
                                        // Проверяем разные структуры данных
                                        $flatList = [];
                                        
                                        // Если есть grouped_data, извлекаем квартиры из групп
                                        if (isset($apartmentsData['grouped_data']) && is_array($apartmentsData['grouped_data'])) {
                                            foreach ($apartmentsData['grouped_data'] as $key => $groups) {
                                                if (is_array($groups)) {
                                                    foreach ($groups as $group) {
                                                        if (isset($group['apartments']) && is_array($group['apartments'])) {
                                                            $flatList = array_merge($flatList, $group['apartments']);
                                                        } elseif (isset($group['_id']) || isset($group['id'])) {
                                                            // Если элемент сам является квартирой
                                                            $flatList[] = $group;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        
                                        // Если grouped_data пуст, используем прямой массив
                                        if (empty($flatList)) {
                                            $flatList = is_array($apartments) ? $apartments : [];
                                        }
                                        
                                        // Ищем квартиру по ID (проверяем и _id и id)
                                        foreach ($flatList as $apt) {
                                            $aptId = $apt['_id'] ?? $apt['id'] ?? null;
                                            if ($aptId === $apartmentId || (string)$aptId === (string)$apartmentId) {
                                                Log::info('Квартира найдена в списке блока (после Fallback 2 500)', [
                                                    'apartment_id' => $apartmentId,
                                                    'found_id' => $aptId,
                                                ]);
                                                return [
                                                    'success' => true,
                                                    'data' => $apt,
                                                    'raw_response' => $apt,
                                                    'source' => 'block_apartments_list_after_fallback2_500',
                                                ];
                                            }
                                        }
                                        
                                        Log::warning('Квартира не найдена в списке блока после Fallback 2 500', [
                                            'apartment_id' => $apartmentId,
                                            'searched_in_count' => count($flatList),
                                        ]);
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::error('Не удалось получить квартиру из списка блока после Fallback 2 500', [
                                    'apartment_id' => $apartmentId,
                                    'block_id' => $blockId,
                                    'error' => $e->getMessage(),
                                    'trace' => substr($e->getTraceAsString(), 0, 500),
                                ]);
                            }
                        }
                        
                        // Если Fallback 2 вернул ошибку и у нас есть blockId, пробуем еще раз получить из списка
                        if ($statusCode !== 200 && $blockId) {
                            // Если квартира не найдена в Fallback 1, пробуем еще раз с пагинацией
                            Log::info('Fallback 2 не удался, пробуем получить из списка блока с пагинацией', [
                                'apartment_id' => $apartmentId,
                                'block_id' => $blockId,
                                'status_code' => $statusCode,
                            ]);
                            
                            try {
                                // Пробуем получить с разными offset
                                for ($offset = 0; $offset < 10000; $offset += 1000) {
                                    $apartmentsData = $this->getBlockApartments($blockId, ['count' => 1000, 'offset' => $offset, 'onrequest' => true]);
                                    
                                    if (isset($apartmentsData['data']) && is_array($apartmentsData['data'])) {
                                        $flatList = $apartmentsData['data'];
                                        
                                        // Если есть grouped_data, извлекаем квартиры
                                        if (isset($apartmentsData['grouped_data']) && is_array($apartmentsData['grouped_data'])) {
                                            $flatList = [];
                                            foreach ($apartmentsData['grouped_data'] as $key => $groups) {
                                                if (is_array($groups)) {
                                                    foreach ($groups as $group) {
                                                        if (isset($group['apartments']) && is_array($group['apartments'])) {
                                                            $flatList = array_merge($flatList, $group['apartments']);
                                                        } elseif (isset($group['_id']) || isset($group['id'])) {
                                                            $flatList[] = $group;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        
                                        foreach ($flatList as $apt) {
                                            $aptId = $apt['_id'] ?? $apt['id'] ?? null;
                                            if ($aptId === $apartmentId || (string)$aptId === (string)$apartmentId) {
                                                Log::info('Квартира найдена в списке блока с пагинацией', [
                                                    'apartment_id' => $apartmentId,
                                                    'offset' => $offset,
                                                ]);
                                                return [
                                                    'success' => true,
                                                    'data' => $apt,
                                                    'raw_response' => $apt,
                                                    'source' => 'block_apartments_list_paginated',
                                                ];
                                            }
                                        }
                                        
                                        // Если получили меньше запрошенного, значит это последняя страница
                                        if (count($flatList) < 1000) {
                                            break;
                                        }
                                    } else {
                                        break;
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::warning('Ошибка при поиске квартиры с пагинацией', [
                                    'apartment_id' => $apartmentId,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                        
                        // Если все fallback не сработали, выбрасываем исключение
                        if ($statusCode !== 200) {
                            $errorMessage = "API вернул статус {$statusCode}";
                            $errorCodeName = null;
                            
                            if (isset($body) && strlen($body) > 0) {
                                // Пытаемся извлечь информацию об ошибке из JSON
                                $errorData = json_decode($body, true);
                                if (isset($errorData['errors']['codeName'])) {
                                    $errorCodeName = $errorData['errors']['codeName'];
                                    $errorMessage .= ': ' . $errorCodeName;
                                } else {
                                    $errorMessage .= ': ' . substr($body, 0, 200);
                                }
                            }
                            
                            // Если это Location17124 и есть blockId, пробуем еще раз получить из списка
                            if ($errorCodeName === 'Location17124' && $blockId) {
                                Log::info('Обнаружена Location17124 в финальной обработке, пробуем получить из списка блока', [
                                    'apartment_id' => $apartmentId,
                                    'block_id' => $blockId,
                                ]);
                                
                                try {
                                    // Получаем список квартир блока с большим лимитом
                                    $apartmentsData = $this->getBlockApartments($blockId, ['count' => 5000, 'offset' => 0]);
                                    
                                    if (isset($apartmentsData['data'])) {
                                        $apartments = $apartmentsData['data'];
                                        if (is_array($apartments)) {
                                            $flatList = [];
                                            
                                            // Если есть grouped_data, извлекаем квартиры
                                            if (isset($apartmentsData['grouped_data']) && is_array($apartmentsData['grouped_data'])) {
                                                foreach ($apartmentsData['grouped_data'] as $key => $groups) {
                                                    if (is_array($groups)) {
                                                        foreach ($groups as $group) {
                                                            if (isset($group['apartments']) && is_array($group['apartments'])) {
                                                                $flatList = array_merge($flatList, $group['apartments']);
                                                            } elseif (isset($group['_id']) || isset($group['id'])) {
                                                                $flatList[] = $group;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            // Если grouped_data пуст, используем прямой массив
                                            if (empty($flatList)) {
                                                $flatList = is_array($apartments) ? $apartments : [];
                                            }
                                            
                                            // Ищем квартиру по ID
                                            foreach ($flatList as $apt) {
                                                $aptId = $apt['_id'] ?? $apt['id'] ?? null;
                                                if ($aptId === $apartmentId || (string)$aptId === (string)$apartmentId) {
                                                    Log::info('Квартира найдена в списке блока после Location17124 (финальная обработка)', [
                                                        'apartment_id' => $apartmentId,
                                                        'found_id' => $aptId,
                                                    ]);
                                                    return [
                                                        'success' => true,
                                                        'data' => $apt,
                                                        'raw_response' => $apt,
                                                        'source' => 'block_apartments_list_location17124_final',
                                                    ];
                                                }
                                            }
                                            
                                            // Если не найдено, пробуем с пагинацией
                                            for ($offset = 1000; $offset < 10000; $offset += 1000) {
                                                $apartmentsData = $this->getBlockApartments($blockId, ['count' => 1000, 'offset' => $offset, 'onrequest' => true]);
                                                
                                                if (isset($apartmentsData['data']) && is_array($apartmentsData['data'])) {
                                                    $flatList = $apartmentsData['data'];
                                                    
                                                    if (isset($apartmentsData['grouped_data']) && is_array($apartmentsData['grouped_data'])) {
                                                        $flatList = [];
                                                        foreach ($apartmentsData['grouped_data'] as $key => $groups) {
                                                            if (is_array($groups)) {
                                                                foreach ($groups as $group) {
                                                                    if (isset($group['apartments']) && is_array($group['apartments'])) {
                                                                        $flatList = array_merge($flatList, $group['apartments']);
                                                                    } elseif (isset($group['_id']) || isset($group['id'])) {
                                                                        $flatList[] = $group;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    
                                                    foreach ($flatList as $apt) {
                                                        $aptId = $apt['_id'] ?? $apt['id'] ?? null;
                                                        if ($aptId === $apartmentId || (string)$aptId === (string)$apartmentId) {
                                                            Log::info('Квартира найдена в списке блока с пагинацией после Location17124 (финальная обработка)', [
                                                                'apartment_id' => $apartmentId,
                                                                'offset' => $offset,
                                                            ]);
                                                            return [
                                                                'success' => true,
                                                                'data' => $apt,
                                                                'raw_response' => $apt,
                                                                'source' => 'block_apartments_list_paginated_location17124_final',
                                                            ];
                                                        }
                                                    }
                                                    
                                                    if (count($flatList) < 1000) {
                                                        break;
                                                    }
                                                } else {
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    Log::warning('Не удалось получить квартиру из списка блока после Location17124 (финальная обработка)', [
                                        'apartment_id' => $apartmentId,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }
                            
                            Log::error('Все fallback не сработали для apartment detail', [
                                'apartment_id' => $apartmentId,
                                'block_id' => $blockId,
                                'status_code' => $statusCode,
                                'error_message' => $errorMessage,
                                'error_code_name' => $errorCodeName,
                            ]);
                            throw new \Exception($errorMessage);
                        }
                    } catch (\Exception $e) {
                        Log::error('Ошибка при fallback запросе', [
                            'apartment_id' => $apartmentId,
                            'block_id' => $blockId,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e;
                    }
                }
                
                // Если fallback не выполнен и статус не 200, выбрасываем исключение
                if (!$useFallback && isset($statusCode) && $statusCode !== 200) {
                    // Если fallback 2 вернул 500 и у нас есть blockId, пробуем получить из списка блока
                    if ($statusCode === 500 && $blockId) {
                        Log::info('Fallback 2 вернул 500, пробуем получить из списка блока', [
                            'apartment_id' => $apartmentId,
                            'block_id' => $blockId,
                        ]);
                        
                        try {
                            // Получаем список квартир блока, включая квартиры "по запросу"
                            $apartmentsData = $this->getBlockApartments($blockId, ['count' => 1000, 'onrequest' => true]);
                            
                            // Ищем нужную квартиру в списке
                            if (isset($apartmentsData['data'])) {
                                $apartments = $apartmentsData['data'];
                                if (is_array($apartments)) {
                                    // Проверяем разные структуры данных
                                    $flatList = [];
                                    if (isset($apartments['grouped_data'])) {
                                        foreach ($apartments['grouped_data'] as $group) {
                                            if (isset($group['apartments']) && is_array($group['apartments'])) {
                                                $flatList = array_merge($flatList, $group['apartments']);
                                            }
                                        }
                                    } else {
                                        $flatList = is_array($apartments) ? $apartments : [];
                                    }
                                    
                                    // Ищем квартиру по ID
                                    foreach ($flatList as $apt) {
                                        $aptId = $apt['_id'] ?? $apt['id'] ?? null;
                                        if ($aptId === $apartmentId) {
                                            Log::info('Квартира найдена в списке блока (после 500)', [
                                                'apartment_id' => $apartmentId,
                                            ]);
                                            return [
                                                'success' => true,
                                                'data' => $apt,
                                                'raw_response' => $apt,
                                                'source' => 'block_apartments_list_after_500',
                                            ];
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Не удалось получить квартиру из списка блока после 500', [
                                'apartment_id' => $apartmentId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    
                    // Улучшенная обработка ошибок
                    $errorMessage = "API вернул статус {$statusCode}";
                    if (strlen($body) > 0) {
                        // Если ответ HTML, пытаемся извлечь текст ошибки
                        if (strpos($body, '<') !== false) {
                            preg_match('/<pre>(.*?)<\/pre>/s', $body, $matches);
                            if (!empty($matches[1])) {
                                $errorMessage .= ': ' . trim(strip_tags($matches[1]));
                            } else {
                                $errorMessage .= ': ' . substr(strip_tags($body), 0, 200);
                            }
                        } else {
                            // Пытаемся извлечь информацию об ошибке из JSON
                            $errorData = json_decode($body, true);
                            if (isset($errorData['errors']['codeName'])) {
                                $errorCodeName = $errorData['errors']['codeName'];
                                $errorMessage .= ': ' . $errorCodeName;
                                
                                // Специальная обработка для Location17124 - это ошибка MongoDB геолокации
                                // Для таких квартир нужно попробовать получить из списка блока
                                if ($errorCodeName === 'Location17124' && $blockId) {
                                    Log::warning('Обнаружена ошибка Location17124 в обработке ошибок, пробуем получить из списка блока', [
                                        'apartment_id' => $apartmentId,
                                        'block_id' => $blockId,
                                        'status_code' => $statusCode,
                                        'context' => 'error_handling_block',
                                    ]);
                                    
                                    try {
                                        // Получаем список квартир блока с большим лимитом
                                        $apartmentsData = $this->getBlockApartments($blockId, ['count' => 5000, 'offset' => 0]);
                                        
                                        if (isset($apartmentsData['data'])) {
                                            $apartments = $apartmentsData['data'];
                                            if (is_array($apartments)) {
                                                $flatList = [];
                                                
                                                // Если есть grouped_data, извлекаем квартиры
                                                if (isset($apartmentsData['grouped_data']) && is_array($apartmentsData['grouped_data'])) {
                                                    foreach ($apartmentsData['grouped_data'] as $key => $groups) {
                                                        if (is_array($groups)) {
                                                            foreach ($groups as $group) {
                                                                if (isset($group['apartments']) && is_array($group['apartments'])) {
                                                                    $flatList = array_merge($flatList, $group['apartments']);
                                                                } elseif (isset($group['_id']) || isset($group['id'])) {
                                                                    $flatList[] = $group;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                // Если grouped_data пуст, используем прямой массив
                                                if (empty($flatList)) {
                                                    $flatList = is_array($apartments) ? $apartments : [];
                                                }
                                                
                                                // Ищем квартиру по ID
                                                foreach ($flatList as $apt) {
                                                    $aptId = $apt['_id'] ?? $apt['id'] ?? null;
                                                    if ($aptId === $apartmentId || (string)$aptId === (string)$apartmentId) {
                                                        Log::info('Квартира найдена в списке блока после Location17124', [
                                                            'apartment_id' => $apartmentId,
                                                            'found_id' => $aptId,
                                                        ]);
                                                        return [
                                                            'success' => true,
                                                            'data' => $apt,
                                                            'raw_response' => $apt,
                                                            'source' => 'block_apartments_list_location17124',
                                                        ];
                                                    }
                                                }
                                                
                                                // Если не найдено, пробуем с пагинацией
                                                for ($offset = 0; $offset < 10000; $offset += 1000) {
                                                    if ($offset === 0) continue; // Уже проверили
                                                    
                                                    $apartmentsData = $this->getBlockApartments($blockId, ['count' => 1000, 'offset' => $offset, 'onrequest' => true]);
                                                    
                                                    if (isset($apartmentsData['data']) && is_array($apartmentsData['data'])) {
                                                        $flatList = $apartmentsData['data'];
                                                        
                                                        if (isset($apartmentsData['grouped_data']) && is_array($apartmentsData['grouped_data'])) {
                                                            $flatList = [];
                                                            foreach ($apartmentsData['grouped_data'] as $key => $groups) {
                                                                if (is_array($groups)) {
                                                                    foreach ($groups as $group) {
                                                                        if (isset($group['apartments']) && is_array($group['apartments'])) {
                                                                            $flatList = array_merge($flatList, $group['apartments']);
                                                                        } elseif (isset($group['_id']) || isset($group['id'])) {
                                                                            $flatList[] = $group;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        
                                                        foreach ($flatList as $apt) {
                                                            $aptId = $apt['_id'] ?? $apt['id'] ?? null;
                                                            if ($aptId === $apartmentId || (string)$aptId === (string)$apartmentId) {
                                                                Log::info('Квартира найдена в списке блока с пагинацией после Location17124', [
                                                                    'apartment_id' => $apartmentId,
                                                                    'offset' => $offset,
                                                                ]);
                                                                return [
                                                                    'success' => true,
                                                                    'data' => $apt,
                                                                    'raw_response' => $apt,
                                                                    'source' => 'block_apartments_list_paginated_location17124',
                                                                ];
                                                            }
                                                        }
                                                        
                                                        if (count($flatList) < 1000) {
                                                            break;
                                                        }
                                                    } else {
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        Log::warning('Не удалось получить квартиру из списка блока после Location17124', [
                                            'apartment_id' => $apartmentId,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                }
                            } else {
                                $errorMessage .= ': ' . substr($body, 0, 200);
                            }
                        }
                    }
                    Log::error('Ошибка при получении детальной информации о квартире', [
                        'apartment_id' => $apartmentId,
                        'block_id' => $blockId,
                        'status_code' => $statusCode,
                        'url' => $fullUrl,
                        'body_preview' => substr($body, 0, 500),
                    ]);
                    throw new \Exception($errorMessage);
                }

                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
                }

                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data,
                    'raw_response' => $data,
                ];
            } catch (GuzzleException $e) {
                Log::error('GuzzleException при получении детальной информации о квартире', [
                    'apartment_id' => $apartmentId,
                    'block_id' => $blockId,
                    'error' => $e->getMessage(),
                ]);
                throw new \Exception('Ошибка при получении детальной информации о квартире: ' . $e->getMessage());
            } catch (\Exception $e) {
                Log::error('Общая ошибка при получении детальной информации о квартире', [
                    'apartment_id' => $apartmentId,
                    'block_id' => $blockId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Справочник для поэтажного плана: корпуса, секции, этажи по блоку
     * GET api.trendagent.ru/v4_29/apartments/floor_plan/directory/{blockId}
     *
     * @param string $blockId ID блока (ЖК)
     * @param array $params city, lang
     * @return array
     * @throws \Exception
     */
    public function getFloorPlanDirectory(string $blockId, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = "https://api.trendagent.ru/v4_29/apartments/floor_plan/directory/{$blockId}";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении справочника поэтажного плана: ' . $e->getMessage());
        }
    }

    /**
     * Данные плана этажа для отрисовки поэтажного плана
     * GET api.trendagent.ru/v4_29/apartments/floor_plan?building_id=&section_id=&floor_number=
     *
     * @param string $buildingId ID корпуса
     * @param string $sectionId ID секции
     * @param int|string $floorNumber Номер этажа
     * @param array $params city, lang
     * @return array
     * @throws \Exception
     */
    public function getFloorPlan(string $buildingId, string $sectionId, $floorNumber, array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
                'building_id' => $buildingId,
                'section_id' => $sectionId,
                'floor_number' => (string) $floorNumber,
            ];
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = 'https://api.trendagent.ru/v4_29/apartments/floor_plan';
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
                'raw_response' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении поэтажного плана: ' . $e->getMessage());
        }
    }

    /**
     * Получение вознаграждений
     *
     * @param string $blockId ID блока
     * @param string|null $builderId ID застройщика
     * @return array Данные о вознаграждениях
     * @throws \Exception
     */
    public function getRewards(string $blockId, ?string $builderId = null): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $queryParams = [
                'auth_token' => $authToken,
                'block' => $blockId,
            ];
            if ($builderId) {
                $queryParams['builder'] = $builderId;
            }

            $apiUrl = "https://rewards-api.trendagent.ru/builder-reward-settings";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении вознаграждений: ' . $e->getMessage());
        }
    }

    /**
     * Получение акций и скидок
     * 
     * @param string $blockId ID блока
     * @param string|null $builderId ID застройщика
     * @return array Данные об акциях и скидках
     * @throws \Exception
     */
    public function getDiscounts(string $blockId, ?string $builderId = null): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $queryParams = [
                'auth_token' => $authToken,
            ];
            if ($builderId) {
                $queryParams['builder'] = $builderId;
            }

            $apiUrl = "https://discounts.trendagent.ru/blocks/{$blockId}/discounts";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении акций и скидок: ' . $e->getMessage());
        }
    }

    /**
     * Получение программ ипотеки
     * 
     * @param string $blockId ID блока
     * @return array Данные об ипотечных программах
     * @throws \Exception
     */
    public function getMortgage(string $blockId): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $queryParams = [
                'auth_token' => $authToken,
                'premiseType' => 'apartment',
            ];

            $apiUrl = "https://mortgage-api.trendagent.ru/blocks/{$blockId}/";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении программ ипотеки: ' . $e->getMessage());
        }
    }

    /**
     * Получение программ рассрочки
     * 
     * @param string $blockId ID блока
     * @return array Данные о программах рассрочки
     * @throws \Exception
     */
    public function getInstallments(string $blockId): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $queryParams = [
                'auth_token' => $authToken,
            ];

            $apiUrl = "https://tiny-installments-api.trendagent.ru/v1/blocks/{$blockId}";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении программ рассрочки: ' . $e->getMessage());
        }
    }

    /**
     * Получение банков эскроу
     * 
     * @param string $blockId ID блока
     * @return array Данные о банках эскроу
     * @throws \Exception
     */
    public function getBanks(string $blockId): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $defaultParams = [
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
                'cache' => 'false',
                'formating' => 'true',
            ];
            $queryParams = array_merge($defaultParams, []);
            $queryParams['auth_token'] = $authToken;

            $apiUrl = "https://api.trendagent.ru/v4_29/blocks/{$blockId}/bank/";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении банков эскроу: ' . $e->getMessage());
        }
    }

    /**
     * Получение контактов объекта
     * 
     * @param string $blockId ID блока
     * @return array Данные о контактах
     * @throws \Exception
     */
    public function getContacts(string $blockId): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $queryParams = [
                'auth_token' => $authToken,
            ];

            $apiUrl = "https://contacts-api.trendagent.ru/contacts/blocks/{$blockId}";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении контактов: ' . $e->getMessage());
        }
    }

    /**
     * Получение метаданных 3D тура
     * 
     * @param string $blockId ID блока
     * @return array Данные о 3D туре
     * @throws \Exception
     */
    public function get3DTour(string $blockId): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден');
            }

            $queryParams = [
                'auth_token' => $authToken,
            ];

            $apiUrl = "https://3d-tour-api.trendagent.ru/v1/blocks/{$blockId}";
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

            $response = $this->client->get($fullUrl, [
                'headers' => $this->getAuthHeaders(),
                'timeout' => 30,
                'verify' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Ошибка при получении 3D тура: ' . $e->getMessage());
        }
    }

    /**
     * Получение списка квартир через API /v4_29/apartments/search/
     * 
     * @param array $params Параметры запроса (count, offset, sort, sort_order, city, lang и т.д.)
     * @return array Массив с данными квартир
     * @throws \Exception
     */
    public function getApartmentsSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        // Генерируем ключ кэша
        $cacheKey = $this->getCacheKey('apartments_search', $params);
        
        // Пытаемся получить данные из кэша (60 минут)
        try {
            return Cache::remember($cacheKey, 60 * 60, function () use ($params) {
                return $this->executeApartmentsSearchRequest($params);
            });
        } catch (\Exception $cacheException) {
            // Если кеш недоступен, выполняем запрос без кеша
            Log::warning('Кеш недоступен, выполняем запрос без кеша', [
                'error' => $cacheException->getMessage(),
            ]);
            return $this->executeApartmentsSearchRequest($params);
        }
    }
    
    /**
     * Выполнить запрос apartments/search без кеша
     */
    private function executeApartmentsSearchRequest(array $params): array
    {
        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API
            $apiUrl = 'https://api.trendagent.ru/v4_29/apartments/search/';
            
            // Параметры по умолчанию
            $defaultParams = [
                'sort' => 'price',
                'sort_order' => 'asc',
                'count' => 50,
                'offset' => 0,
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            
            // Обрабатываем параметр room для правильного формирования URL
            // room может быть массивом [30, 40] и должен стать room=30&room=40
            $roomParams = [];
            if (isset($queryParams['room']) && is_array($queryParams['room'])) {
                $roomParams = $queryParams['room'];
                unset($queryParams['room']); // Удаляем из основных параметров
            } elseif (isset($queryParams['room'])) {
                // Если одно значение, делаем массив
                $roomParams = [$queryParams['room']];
                unset($queryParams['room']);
            }
            
            $queryParams['auth_token'] = $authToken;
            
            // Формируем базовый query string
            $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
            
            // Добавляем параметры room отдельно (room=30&room=40)
            if (!empty($roomParams)) {
                $roomQuery = [];
                foreach ($roomParams as $room) {
                    $roomQuery[] = 'room=' . urlencode($room);
                }
                if (!empty($queryString)) {
                    $queryString .= '&' . implode('&', $roomQuery);
                } else {
                    $queryString = implode('&', $roomQuery);
                }
            }
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . $queryString;

            Log::info('Запрос к API apartments/search', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
                'has_room_filter' => !empty($roomParams),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Origin' => 'https://spb.trendagent.ru',
                    'Referer' => 'https://spb.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 304) {
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('API вернул ошибку', [
                    'status_code' => $statusCode,
                    'body_preview' => substr($body, 0, 500),
                ]);
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON ответа: ' . json_last_error_msg());
            }

            if (isset($data['errors']) && !empty($data['errors'])) {
                throw new \Exception('API вернул ошибки: ' . json_encode($data['errors']));
            }

            // Для apartments/search структура ответа:
            // { "data": { "apartmentsCount": 55551, "blocksCount": 344, "list": [...] }, "errors": [] }
            $apartmentsList = $data['data']['list'] ?? [];
            $apartmentsCount = $data['data']['apartmentsCount'] ?? 0;
            
            Log::info('Данные получены через API apartments/search', [
                'results_count' => count($apartmentsList),
                'apartmentsCount' => $apartmentsCount,
                'blocksCount' => $data['data']['blocksCount'] ?? 0,
            ]);

            return [
                'success' => true,
                'data' => $apartmentsList,
                'total' => $apartmentsCount,
                'blocks_count' => $data['data']['blocksCount'] ?? 0,
                'source' => 'api',
            ];
        } catch (GuzzleException $e) {
            Log::error('Ошибка при запросе к API apartments/search', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при запросе к API: ' . $e->getMessage());
        }
    }

    /**
     * Получение списка машиномест через API parkings-api.trendagent.ru/search/places/
     * 
     * @param array $params Параметры запроса (count, offset, sort, sort_order, city, lang и т.д.)
     * @return array Массив с данными машиномест
     * @throws \Exception
     */
    public function getParkingPlacesSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для машиномест
            $apiUrl = 'https://parkings-api.trendagent.ru/search/places/';
            
            // Параметры по умолчанию
            $defaultParams = [
                'sort' => 'price',
                'sort_order' => 'asc',
                'count' => 50,
                'offset' => 0,
                'number' => '',
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API parkings/search/places', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Origin' => 'https://spb.trendagent.ru',
                    'Referer' => 'https://spb.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API паркинга (places)', [
                    'status_code' => $statusCode,
                    'response_body' => substr($body, 0, 500),
                ]);
                throw new \Exception("Ошибка при запросе к API паркинга: HTTP {$statusCode}");
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Структура ответа parkings-api: { "placesCount": 3635, "blocksCount": 50, "results": [...] }
            $placesList = $data['results'] ?? $data['data'] ?? [];
            $placesCount = $data['placesCount'] ?? $data['total'] ?? 0;
            $blocksCount = $data['blocksCount'] ?? 0;

            return [
                'success' => true,
                'data' => $placesList,
                'total' => $placesCount,
                'blocks_count' => $blocksCount,
                'source' => 'parkings_api',
            ];
        } catch (GuzzleException $e) {
            Log::error('Ошибка при запросе к API паркинга (places)', [
                'message' => $e->getMessage(),
            ]);
            throw new \Exception('Ошибка при запросе к API: ' . $e->getMessage());
        }
    }


    /**
     * Получение списка коммерческих помещений через API commerce-api.trendagent.ru/search/premises
     * 
     * @param array $params Параметры запроса (count, offset, sort, sort_order, city, lang и т.д.)
     * @return array Массив с данными помещений
     * @throws \Exception
     */
    public function getCommercePremisesSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            $authToken = $this->getAuthToken();
            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для коммерческих помещений
            $apiUrl = 'https://commerce-api.trendagent.ru/search/premises';
            
            // Параметры по умолчанию
            $defaultParams = [
                'sort' => 'price',
                'sort_order' => 'asc',
                'count' => 50,
                'offset' => 0,
                'number' => '',
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API commerce/search/premises', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Origin' => 'https://spb.trendagent.ru',
                    'Referer' => 'https://spb.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API коммерции (premises)', [
                    'status_code' => $statusCode,
                    'response_body' => substr($body, 0, 500),
                ]);
                throw new \Exception("Ошибка при запросе к API коммерции: HTTP {$statusCode}");
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Структура ответа commerce-api: { "premises_count": 1775, "blocks_count": 168, "result": [...] }
            $premisesList = $data['result'] ?? $data['data'] ?? $data['results'] ?? [];
            $premisesCount = $data['premises_count'] ?? $data['total'] ?? $data['premisesCount'] ?? 0;
            $blocksCount = $data['blocks_count'] ?? $data['blocksCount'] ?? 0;

            return [
                'success' => true,
                'data' => $premisesList,
                'total' => $premisesCount,
                'blocks_count' => $blocksCount, // Количество ЖК с коммерцией
                'source' => 'commerce_api',
            ];
        } catch (GuzzleException $e) {
            Log::error('Ошибка при запросе к API коммерции (premises)', [
                'message' => $e->getMessage(),
            ]);
            throw new \Exception('Ошибка при запросе к API: ' . $e->getMessage());
        }
    }
}

