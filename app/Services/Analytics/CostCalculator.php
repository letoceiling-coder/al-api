<?php

namespace App\Services\Analytics;

class CostCalculator
{
    /**
     * Calculate cost for AI request
     * 
     * @param string $provider
     * @param string $model
     * @param int $promptTokens
     * @param int $completionTokens
     * @return float Cost in USD
     */
    public function calculate(
        string $provider,
        string $model,
        int $promptTokens,
        int $completionTokens
    ): float {
        $modelConfig = config("ai.{$provider}.models.{$model}");

        if (!$modelConfig) {
            return 0.0;
        }

        $inputPrice = $modelConfig['input_price'] ?? 0;
        $outputPrice = $modelConfig['output_price'] ?? 0;

        // Prices are per 1M tokens, convert to actual cost
        $inputCost = ($promptTokens / 1_000_000) * $inputPrice;
        $outputCost = ($completionTokens / 1_000_000) * $outputPrice;

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Get model configuration
     */
    public function getModelConfig(string $provider, string $model): ?array
    {
        return config("ai.{$provider}.models.{$model}");
    }

    /**
     * Check if model supports vision/files
     */
    public function supportsVision(string $provider, string $model): bool
    {
        $modelConfig = $this->getModelConfig($provider, $model);
        return $modelConfig['supports_vision'] ?? false;
    }

    /**
     * Get max tokens for model
     */
    public function getMaxTokens(string $provider, string $model): int
    {
        $modelConfig = $this->getModelConfig($provider, $model);
        return $modelConfig['max_tokens'] ?? 4096;
    }
}
