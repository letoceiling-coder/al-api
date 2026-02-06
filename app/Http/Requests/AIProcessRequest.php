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
        $isMultipart = $this->isMultipartRequest();
        
        $rules = [
            // Required fields
            'provider' => ['required', 'string', 'in:' . implode(',', $providers)],
            'model' => ['required', 'string', 'max:100'],
            'prompt' => ['required', 'string', 'min:1'],
            
            // Optional API key
            'user_api_key' => ['nullable', 'string'],
            'use_saved_key' => ['nullable', 'boolean'],
            'saved_key_id' => ['nullable', 'integer', 'exists:user_ai_keys,id'],
            
            // Parameters (dynamic validation based on model)
            'parameters' => ['nullable', 'array'],
            'parameters.temperature' => ['nullable', 'numeric', 'between:0,2'],
            'parameters.max_tokens' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'parameters.max_output_tokens' => ['nullable', 'integer', 'min:1'],
            'parameters.top_p' => ['nullable', 'numeric', 'between:0,1'],
            'parameters.top_k' => ['nullable', 'integer', 'min:1', 'max:100'],
            'parameters.stream' => ['nullable', 'boolean'],
            
            // Gemini-specific parameters
            'parameters.stop_sequences' => ['nullable', 'array', 'max:5'],
            'parameters.stop_sequences.*' => ['string', 'max:500'],
            'parameters.candidate_count' => ['nullable', 'integer', 'min:1', 'max:8'],
            'parameters.safety_settings' => ['nullable', 'array'],
            
            // OpenAI-specific parameters
            'parameters.presence_penalty' => ['nullable', 'numeric', 'between:-2,2'],
            'parameters.frequency_penalty' => ['nullable', 'numeric', 'between:-2,2'],
            'parameters.n' => ['nullable', 'integer', 'min:1', 'max:10'],
            'parameters.stop' => ['nullable', 'array', 'max:4'],
            'parameters.stop.*' => ['string', 'max:500'],
            'parameters.logit_bias' => ['nullable', 'array'],
            'parameters.user' => ['nullable', 'string', 'max:255'],
            'parameters.seed' => ['nullable', 'integer'],
            'parameters.response_format' => ['nullable', 'array'],
            'parameters.response_format.type' => ['nullable', 'string', 'in:text,json_object'],
            
            // Metadata
            'metadata' => ['nullable', 'array'],
        ];

        // Different validation rules for multipart vs JSON
        if ($isMultipart) {
            // Multipart: uploaded files
            $maxFileSize = config('ai.limits.max_file_size_mb', 10) * 1024; // Convert to KB
            $rules['uploaded_files'] = ['nullable', 'array', 'max:10'];
            $rules['uploaded_files.*'] = [
                'required',
                'file',
                'max:' . $maxFileSize,
                'mimes:jpeg,jpg,png,gif,webp,pdf,txt,doc,docx,mp3,mp4,wav',
            ];
        } else {
            // JSON: Base64 files (backward compatibility)
            $rules['files'] = ['nullable', 'array', 'max:10'];
            $rules['files.*.type'] = ['required_with:files', 'string', 'in:image,audio,document'];
            $rules['files.*.content'] = ['required_with:files', 'string'];
            $rules['files.*.mime_type'] = ['required_with:files', 'string'];
            $rules['files.*.name'] = ['nullable', 'string', 'max:255'];
        }
        
        return $rules;
    }

    /**
     * Check if this is a multipart/form-data request
     */
    protected function isMultipartRequest(): bool
    {
        $contentType = $this->header('Content-Type', '');
        return str_contains($contentType, 'multipart/form-data');
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
            'parameters.top_k.max' => 'Top K must not exceed 100',
            'parameters.stop_sequences.max' => 'Maximum 5 stop sequences allowed',
            'parameters.candidate_count.max' => 'Maximum 8 candidates allowed',
            'parameters.presence_penalty.between' => 'Presence penalty must be between -2 and 2',
            'parameters.frequency_penalty.between' => 'Frequency penalty must be between -2 and 2',
            'parameters.n.max' => 'Maximum 10 completion choices allowed',
            'parameters.stop.max' => 'Maximum 4 stop sequences allowed',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Parse JSON parameters if sent as string in multipart
        if ($this->isMultipartRequest() && $this->has('parameters') && is_string($this->parameters)) {
            $this->merge(['parameters' => json_decode($this->parameters, true) ?? []]);
        }

        // Parse JSON metadata if sent as string in multipart
        if ($this->isMultipartRequest() && $this->has('metadata') && is_string($this->metadata)) {
            $this->merge(['metadata' => json_decode($this->metadata, true) ?? []]);
        }

        // Ensure arrays exist
        if (!$this->has('parameters')) {
            $this->merge(['parameters' => []]);
        }

        if (!$this->has('files') && !$this->isMultipartRequest()) {
            $this->merge(['files' => []]);
        }
    }

    /**
     * Get normalized files array (converts multipart to internal format)
     * 
     * @return array
     */
    public function getNormalizedFiles(): array
    {
        if ($this->isMultipartRequest()) {
            return $this->convertMultipartToFiles();
        }
        
        return $this->input('files', []);
    }

    /**
     * Convert uploaded multipart files to internal format
     * 
     * @return array
     */
    protected function convertMultipartToFiles(): array
    {
        $uploadedFiles = $this->file('uploaded_files', []);
        $normalizedFiles = [];

        foreach ($uploadedFiles as $file) {
            $mimeType = $file->getMimeType();
            $type = $this->detectFileType($mimeType);
            
            // Convert to base64 for internal processing
            $content = base64_encode(file_get_contents($file->getRealPath()));
            
            $normalizedFiles[] = [
                'type' => $type,
                'content' => 'data:' . $mimeType . ';base64,' . $content,
                'mime_type' => $mimeType,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];
        }

        return $normalizedFiles;
    }

    /**
     * Detect file type from MIME type
     * 
     * @param string $mimeType
     * @return string
     */
    protected function detectFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }
        
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        
        return 'document';
    }
}
