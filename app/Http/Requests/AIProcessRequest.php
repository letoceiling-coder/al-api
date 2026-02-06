<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIProcessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by Sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $providers = config('ai.providers', ['gemini', 'openai']);
        
        return [
            // Required fields
            'provider' => ['required', 'string', 'in:' . implode(',', $providers)],
            'model' => ['required', 'string', 'max:100'],
            'prompt' => ['required', 'string', 'min:1'],
            
            // Optional API key
            'user_api_key' => ['nullable', 'string'],
            'use_saved_key' => ['nullable', 'boolean'],
            'saved_key_id' => ['nullable', 'integer', 'exists:user_ai_keys,id'],
            
            // Parameters
            'parameters' => ['nullable', 'array'],
            'parameters.temperature' => ['nullable', 'numeric', 'between:0,2'],
            'parameters.max_tokens' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'parameters.top_p' => ['nullable', 'numeric', 'between:0,1'],
            'parameters.top_k' => ['nullable', 'integer', 'min:1'],
            'parameters.stream' => ['nullable', 'boolean'],
            
            // Files
            'files' => ['nullable', 'array', 'max:10'],
            'files.*.type' => ['required_with:files', 'string', 'in:image,audio,document'],
            'files.*.content' => ['required_with:files', 'string'],
            'files.*.mime_type' => ['required_with:files', 'string'],
            'files.*.name' => ['nullable', 'string', 'max:255'],
            
            // Metadata
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'provider.required' => 'AI provider is required (gemini or openai)',
            'provider.in' => 'Invalid provider. Supported: :values',
            'model.required' => 'AI model is required',
            'prompt.required' => 'Prompt text is required',
            'prompt.min' => 'Prompt cannot be empty',
            'files.max' => 'Maximum 10 files allowed per request',
            'parameters.temperature.between' => 'Temperature must be between 0 and 2',
            'parameters.max_tokens.max' => 'Max tokens cannot exceed 1,000,000',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure arrays exist
        if (!$this->has('parameters')) {
            $this->merge(['parameters' => []]);
        }

        if (!$this->has('files')) {
            $this->merge(['files' => []]);
        }
    }
}
