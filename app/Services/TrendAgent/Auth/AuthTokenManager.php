<?php

namespace App\Services\TrendAgent\Auth;

use App\Services\TrendAgent\Core\Errors\AuthExpiredError;
use App\Services\TrendAgent\TrendSsoApiAuth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Менеджер токенов авторизации для TrendAgent API
 * 
 * Управляет жизненным циклом токенов:
 * - Кэширование токенов
 * - Автоматическое обновление перед истечением
 * - Декодирование JWT для проверки exp
 * 
 * Использует TrendSsoApiAuth для получения токенов
 */
class AuthTokenManager
{
    private const TOKEN_CACHE_KEY = 'trendagent_auth_token';
    private const TOKEN_LIFETIME_SECONDS = 300; // 5 минут по умолчанию
    private const REFRESH_BEFORE_SECONDS = 60; // Обновлять за 1 минуту до истечения

    private ?string $currentToken = null;
    private ?int $tokenExpiresAt = null;
    private TrendSsoApiAuth $ssoAuth;

    public function __construct(TrendSsoApiAuth $ssoAuth)
    {
        $this->ssoAuth = $ssoAuth;
    }

    /**
     * Получить валидный токен (из кэша или обновить)
     */
    public function getValidToken(): string
    {
        // Проверить кэш
        $cached = $this->getFromCache();
        if ($cached !== null) {
            return $cached;
        }

        // Проверить текущий токен в памяти
        if ($this->currentToken !== null && !$this->isExpired()) {
            return $this->currentToken;
        }

        // Обновить токен
        return $this->refreshToken();
    }

    /**
     * Обновить токен через TrendSsoApiAuth
     */
    public function refreshToken(): string
    {
        try {
            Log::info('AuthTokenManager: Обновление токена');

            // Использовать существующий TrendSsoApiAuth
            $phone = config('trendagent.phone') ?: env('TRENDAGENT_PHONE', '+79045393434');
            $password = config('trendagent.password') ?: env('TRENDAGENT_PASSWORD', 'nwBvh4q');

            $authData = $this->ssoAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new AuthExpiredError('Авторизация не удалась');
            }

            // Получить токен
            $token = $this->ssoAuth->getAuthToken();

            if (empty($token)) {
                throw new AuthExpiredError('Токен не получен после авторизации');
            }

            // Определить время истечения
            $expiresAt = $this->getExpirationFromToken($token) 
                ?? (time() + self::TOKEN_LIFETIME_SECONDS);

            // Сохранить токен
            $this->setToken($token, $expiresAt);

            Log::info('AuthTokenManager: Токен обновлен', [
                'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                'time_to_expiry' => $expiresAt - time(),
            ]);

            return $token;

        } catch (\Exception $e) {
            Log::error('AuthTokenManager: Ошибка обновления токена', [
                'error' => $e->getMessage(),
            ]);
            throw new AuthExpiredError('Не удалось обновить токен: ' . $e->getMessage());
        }
    }

    /**
     * Проверить, истёк ли текущий токен
     */
    public function isExpired(): bool
    {
        if ($this->tokenExpiresAt === null) {
            return true;
        }

        $now = time();
        $refreshAt = $this->tokenExpiresAt - self::REFRESH_BEFORE_SECONDS;

        return $now >= $refreshAt;
    }

    /**
     * Установить токен вручную (для тестирования или внешней авторизации)
     */
    public function setToken(string $token, ?int $expiresAt = null): void
    {
        $this->currentToken = $token;
        $this->tokenExpiresAt = $expiresAt ?? (time() + self::TOKEN_LIFETIME_SECONDS);

        $this->saveToCache($token, $this->tokenExpiresAt);
    }

    /**
     * Инвалидировать текущий токен
     */
    public function invalidate(): void
    {
        $this->currentToken = null;
        $this->tokenExpiresAt = null;

        Cache::forget(self::TOKEN_CACHE_KEY);

        Log::info('AuthTokenManager: Токен инвалидирован');
    }

    /**
     * Получить время до истечения токена (в секундах)
     */
    public function getTimeToExpiry(): ?int
    {
        if ($this->tokenExpiresAt === null) {
            return null;
        }

        return max(0, $this->tokenExpiresAt - time());
    }

    /**
     * Декодировать JWT токен и получить payload
     */
    public function decodeToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new AuthExpiredError('Invalid JWT token format');
        }

        try {
            $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
            $decoded = json_decode($payload, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new AuthExpiredError('Failed to decode JWT payload: ' . json_last_error_msg());
            }

            return $decoded ?? [];
        } catch (\Exception $e) {
            throw new AuthExpiredError('Error decoding JWT: ' . $e->getMessage());
        }
    }

    /**
     * Получить exp (expiration) из токена
     */
    public function getExpirationFromToken(string $token): ?int
    {
        try {
            $payload = $this->decodeToken($token);
            $exp = $payload['exp'] ?? null;

            if ($exp !== null && is_numeric($exp)) {
                return (int) $exp;
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('AuthTokenManager: Не удалось получить exp из токена', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Сохранить токен в кэш
     */
    private function saveToCache(string $token, int $expiresAt): void
    {
        $ttl = $expiresAt - time();

        if ($ttl > 0) {
            Cache::put(self::TOKEN_CACHE_KEY, [
                'token' => $token,
                'expires_at' => $expiresAt,
            ], $ttl);

            Log::debug('AuthTokenManager: Токен сохранен в кэш', [
                'ttl' => $ttl,
                'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            ]);
        }
    }

    /**
     * Получить токен из кэша
     */
    private function getFromCache(): ?string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if ($cached === null) {
            return null;
        }

        $this->currentToken = $cached['token'] ?? null;
        $this->tokenExpiresAt = $cached['expires_at'] ?? null;

        if ($this->currentToken === null || $this->isExpired()) {
            $this->invalidate();
            return null;
        }

        Log::debug('AuthTokenManager: Токен получен из кэша', [
            'time_to_expiry' => $this->getTimeToExpiry(),
        ]);

        return $this->currentToken;
    }
}
