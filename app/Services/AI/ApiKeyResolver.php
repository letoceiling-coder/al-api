<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\UserAIKey;
use App\Exceptions\InvalidApiKeyException;

class ApiKeyResolver
{
    /**
     * Resolve API key for the given provider and user
     * 
     * @param string $provider
     * @param User $user
     * @param array $request
     * @return array ['key' => string, 'source' => string]
     * @throws InvalidApiKeyException
     */
    public function resolve(string $provider, User $user, array $request): array
    {
        // 1. Check if user keys are required
        if (config('ai.keys.require_user_keys')) {
            return $this->resolveUserKey($provider, $user, $request);
        }

        // 2. Check for API key in request body
        if (isset($request['user_api_key']) && config('ai.keys.allow_in_request')) {
            return [
                'key' => $request['user_api_key'],
                'source' => 'user_request',
            ];
        }

        // 3. Check for saved user key
        if (isset($request['use_saved_key']) && $request['use_saved_key']) {
            $savedKeyId = $request['saved_key_id'] ?? null;
            return $this->resolveSavedKey($provider, $user, $savedKeyId);
        }

        // 4. Check if internal keys are allowed
        if (config('ai.keys.allow_internal')) {
            $internalKey = config("ai.{$provider}.api_key");
            
            if (!$internalKey) {
                throw new InvalidApiKeyException(
                    "Internal API key for {$provider} is not configured"
                );
            }

            return [
                'key' => $internalKey,
                'source' => 'internal',
            ];
        }

        // No valid key found
        throw new InvalidApiKeyException(
            "API key is required. Please provide your {$provider} API key or configure internal keys."
        );
    }

    /**
     * Resolve user-provided key
     */
    protected function resolveUserKey(string $provider, User $user, array $request): array
    {
        // Check request body
        if (isset($request['user_api_key'])) {
            return [
                'key' => $request['user_api_key'],
                'source' => 'user_request',
            ];
        }

        // Check saved keys
        if (config('ai.keys.allow_user_storage')) {
            $savedKey = UserAIKey::where('user_id', $user->id)
                ->where('provider', $provider)
                ->where('is_active', true)
                ->first();

            if ($savedKey) {
                return [
                    'key' => $savedKey->api_key,
                    'source' => 'user_saved',
                ];
            }
        }

        throw new InvalidApiKeyException(
            "API key for {$provider} is required. Please provide your key."
        );
    }

    /**
     * Resolve saved user key by ID
     */
    protected function resolveSavedKey(string $provider, User $user, ?int $keyId): array
    {
        if (!config('ai.keys.allow_user_storage')) {
            throw new InvalidApiKeyException(
                "User key storage is not enabled"
            );
        }

        $query = UserAIKey::where('user_id', $user->id)
            ->where('provider', $provider)
            ->where('is_active', true);

        if ($keyId) {
            $query->where('id', $keyId);
        }

        $savedKey = $query->first();

        if (!$savedKey) {
            throw new InvalidApiKeyException(
                "No active {$provider} key found for this user"
            );
        }

        return [
            'key' => $savedKey->api_key,
            'source' => 'user_saved',
        ];
    }

    /**
     * Validate provider
     */
    public function validateProvider(string $provider): void
    {
        $allowedProviders = config('ai.providers', ['gemini', 'openai']);
        
        if (!in_array($provider, $allowedProviders)) {
            throw new InvalidApiKeyException(
                "Unsupported provider: {$provider}. Allowed: " . implode(', ', $allowedProviders)
            );
        }
    }
}
