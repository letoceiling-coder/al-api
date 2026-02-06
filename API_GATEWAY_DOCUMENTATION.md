# AL API Gateway - Complete Documentation

## Overview

AL API Gateway is a unified API for accessing multiple AI providers (Gemini, OpenAI) with built-in:
- ✅ API key management (internal/user-provided)
- ✅ Usage tracking and analytics
- ✅ Cost calculation
- ✅ Rate limiting
- ✅ Request logging

**Base URL**: `https://api.siteaccess.ru/api`

---

## Authentication

All endpoints (except `/test`) require Sanctum Bearer token authentication.

```http
Authorization: Bearer {your_sanctum_token}
```

---

## Endpoints

### 1. Test Endpoint

**GET** `/test`

Test if API is working.

**Response**:
```json
{
  "message": "AL API is working",
  "version": "1.0.0",
  "status": "success",
  "timestamp": "2026-02-06T14:30:00+00:00"
}
```

---

### 2. Process AI Request (Main Endpoint)

**POST** `/ai/process`

Send a request to AI provider (Gemini or OpenAI).

#### Request Body

```json
{
  "provider": "gemini",              // Required: 'gemini' | 'openai'
  "model": "gemini-1.5-pro",         // Required: model name
  "prompt": "Explain quantum computing", // Required: your prompt
  
  // Optional: API Key (if needed)
  "user_api_key": "AIza...",         // Your API key
  "use_saved_key": true,             // Use saved key from your account
  "saved_key_id": 5,                 // ID of saved key
  
  // Optional: Model Parameters
  "parameters": {
    "temperature": 0.7,              // 0-2 (creativity)
    "max_tokens": 1000,              // Max response length
    "top_p": 1.0,                    // Nucleus sampling
    "stream": false                  // Stream response
  },
  
  // Optional: Files (for vision models)
  "files": [
    {
      "type": "image",               // 'image' | 'audio' | 'document'
      "content": "base64_string",    // Base64 encoded content
      "mime_type": "image/jpeg",
      "name": "photo.jpg"            // Optional filename
    }
  ],
  
  // Optional: Custom metadata
  "metadata": {
    "task_type": "analysis",
    "project_id": "proj_123"
  }
}
```

#### Response (Success)

```json
{
  "success": true,
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "data": {
    "provider": "gemini",
    "model": "gemini-1.5-pro",
    "response": {
      "text": "Quantum computing is...",
      "finish_reason": "stop"
    }
  },
  "usage": {
    "prompt_tokens": 150,
    "completion_tokens": 300,
    "total_tokens": 450,
    "estimated_cost": 0.000675      // in USD
  },
  "metadata": {
    "processing_time": 2.345,       // seconds
    "timestamp": "2026-02-06T14:30:00Z",
    "api_key_source": "internal"    // 'internal' | 'user_request' | 'user_saved'
  },
  "limits": {
    "daily_requests_used": 45,
    "daily_requests_limit": 100,
    "daily_requests_remaining": 55
  }
}
```

#### Response (Error)

```json
{
  "success": false,
  "request_id": "...",
  "error": {
    "type": "InvalidApiKeyException",
    "message": "API key is required"
  }
}
```

#### Supported Models

**Gemini**:
- `gemini-1.5-pro` - Best quality, supports vision
- `gemini-1.5-flash` - Fast, cheaper, supports vision
- `gemini-pro-vision` - Legacy vision model

**OpenAI**:
- `gpt-4-turbo-preview` - Latest GPT-4, best quality
- `gpt-4` - Standard GPT-4
- `gpt-3.5-turbo` - Fast and cheap
- `gpt-4-vision-preview` - GPT-4 with vision

---

### 3. User API Keys Management

#### List Keys

**GET** `/user/keys`

Get all your saved API keys.

**Response**:
```json
{
  "success": true,
  "keys": [
    {
      "id": 1,
      "provider": "gemini",
      "label": "My Gemini Key",
      "masked_key": "AIza****Tg8",
      "is_active": true,
      "created_at": "2026-02-06T14:00:00Z"
    }
  ]
}
```

#### Add Key

**POST** `/user/keys`

Save a new API key.

**Request**:
```json
{
  "provider": "gemini",              // Required: 'gemini' | 'openai'
  "api_key": "AIzaSy...",            // Required: your API key
  "label": "My Gemini Key"           // Optional: friendly name
}
```

**Response**:
```json
{
  "success": true,
  "message": "API key added successfully",
  "key": {
    "id": 1,
    "provider": "gemini",
    "label": "My Gemini Key",
    "masked_key": "AIza****Tg8",
    "is_active": true
  }
}
```

#### Get Key Details

**GET** `/user/keys/{id}`

#### Update Key

**PUT** `/user/keys/{id}`

**Request**:
```json
{
  "label": "Updated Label",
  "is_active": false
}
```

#### Delete Key

**DELETE** `/user/keys/{id}`

#### Toggle Key Status

**PATCH** `/user/keys/{id}/toggle`

Enable/disable a key.

---

### 4. Analytics

#### Get Summary

**GET** `/analytics/summary?start_date=2026-01-01&end_date=2026-02-06`

Get overall usage statistics.

**Response**:
```json
{
  "success": true,
  "summary": {
    "total_requests": 250,
    "successful_requests": 245,
    "failed_requests": 5,
    "total_tokens": 125000,
    "total_cost": 0.625,
    "gemini_requests": 150,
    "openai_requests": 100,
    "daily_breakdown": [...]
  }
}
```

#### Get Request History

**GET** `/analytics/history?limit=50&provider=gemini&status=success`

Get recent requests.

**Query Parameters**:
- `limit` (optional): 1-100, default 50
- `provider` (optional): `gemini` | `openai`
- `status` (optional): `success` | `error` | `rate_limited`

#### Get Costs

**GET** `/analytics/costs`

Get cost breakdown for last 30 days.

**Response**:
```json
{
  "success": true,
  "costs": {
    "total_30_days": 12.50,
    "daily_breakdown": [
      {
        "date": "2026-02-06",
        "cost": 0.85,
        "requests": 45
      }
    ]
  }
}
```

#### Get Current Limits

**GET** `/analytics/limits`

Get your rate limits and usage.

**Response**:
```json
{
  "success": true,
  "limits": {
    "daily_requests": {
      "limit": 100,
      "used": 45,
      "remaining": 55,
      "resets_at": "2026-02-06T23:59:59Z"
    },
    "monthly_tokens": {
      "limit": 100000
    },
    "max_file_size_mb": 10
  }
}
```

#### Get Stats by Provider

**GET** `/analytics/by-provider`

#### Get Stats by Model

**GET** `/analytics/by-model`

---

## Configuration (.env flags)

### API Key Management

```env
# Allow using internal keys from .env
ALLOW_INTERNAL_API_KEYS=true

# Require users to provide their own keys
REQUIRE_USER_API_KEYS=false

# Allow passing keys in request body
ALLOW_API_KEY_IN_REQUEST=true

# Allow saving keys in database
ALLOW_USER_KEY_STORAGE=true
```

### Usage Limits

```env
DEFAULT_DAILY_REQUEST_LIMIT=100
DEFAULT_MONTHLY_TOKEN_LIMIT=100000
DEFAULT_MAX_FILE_SIZE_MB=10
```

### Features

```env
ENABLE_REQUEST_LOGGING=true
ENABLE_USAGE_ANALYTICS=true
ENABLE_RATE_LIMITING=true
ENABLE_COST_TRACKING=true
```

---

## API Key Logic

The system selects API key in this priority order:

1. **If `REQUIRE_USER_API_KEYS=true`** → Must provide user key
2. **If `user_api_key` in request** → Use that key
3. **If `use_saved_key=true`** → Use saved key from database
4. **If `ALLOW_INTERNAL_API_KEYS=true`** → Use internal key from .env
5. **Otherwise** → Return error "API key required"

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "error": {
    "type": "InvalidApiKey",
    "message": "API key is required"
  }
}
```

### 429 Rate Limit Exceeded
```json
{
  "success": false,
  "error": {
    "type": "RateLimitExceeded",
    "message": "Daily request limit of 100 exceeded",
    "retry_after": 86400
  }
}
```

### 500 Server Error
```json
{
  "success": false,
  "error": {
    "type": "AIProviderError",
    "message": "Provider returned an error",
    "provider": "gemini"
  }
}
```

---

## Examples

### Example 1: Simple Text Generation (Using Internal Key)

```bash
curl -X POST https://api.siteaccess.ru/api/ai/process \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "gemini",
    "model": "gemini-1.5-flash",
    "prompt": "Write a haiku about programming"
  }'
```

### Example 2: Image Analysis (With User Key)

```bash
curl -X POST https://api.siteaccess.ru/api/ai/process \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "gemini",
    "model": "gemini-1.5-pro",
    "prompt": "What is in this image?",
    "user_api_key": "AIzaSy...",
    "files": [
      {
        "type": "image",
        "content": "base64_encoded_image_data",
        "mime_type": "image/jpeg"
      }
    ]
  }'
```

### Example 3: Using Saved Key

```bash
curl -X POST https://api.siteaccess.ru/api/ai/process \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "openai",
    "model": "gpt-4-turbo-preview",
    "prompt": "Explain machine learning",
    "use_saved_key": true,
    "saved_key_id": 5,
    "parameters": {
      "temperature": 0.7,
      "max_tokens": 500
    }
  }'
```

### Example 4: Add API Key

```bash
curl -X POST https://api.siteaccess.ru/api/user/keys \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "gemini",
    "api_key": "AIzaSyBUwk...",
    "label": "Production Key"
  }'
```

### Example 5: Get Analytics

```bash
curl -X GET "https://api.siteaccess.ru/api/analytics/summary?start_date=2026-02-01&end_date=2026-02-06" \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN"
```

---

## Pricing Reference

### Gemini (per 1M tokens)
- **Gemini 1.5 Pro**: $0.50 input / $1.50 output
- **Gemini 1.5 Flash**: $0.10 input / $0.30 output

### OpenAI (per 1M tokens)
- **GPT-4 Turbo**: $10.00 input / $30.00 output
- **GPT-3.5 Turbo**: $0.50 input / $1.50 output

*Prices are estimates and may vary. Check provider documentation for current pricing.*

---

## Best Practices

1. **Use Flash models for simple tasks** - They're faster and cheaper
2. **Set appropriate max_tokens** - Avoid unnecessary costs
3. **Save your API keys** - Easier to manage and rotate
4. **Monitor your usage** - Use analytics endpoints
5. **Handle rate limits** - Implement retry logic with exponential backoff
6. **Validate files before sending** - Check size and format
7. **Use temperature wisely** - Lower for factual, higher for creative

---

## Support & Contact

For issues or questions, please contact your administrator or check the project documentation.

**Version**: 1.0.0  
**Last Updated**: 2026-02-06
