# API Документация - AI Модели

## Базовый URL
```
https://api.siteaccess.ru/api
```

## Аутентификация
Все endpoints требуют Bearer токен в заголовке:
```
Authorization: Bearer YOUR_TOKEN
```

---

## Gemini API

### 1. Генерация текста
**POST** `/api/gemini/generate`

**Параметры:**
```json
{
  "prompt": "Напиши рассказ о космосе",
  "model": "gemini-1.5-pro", // опционально
  "temperature": 0.7, // опционально
  "maxOutputTokens": 2048 // опционально
}
```

**Доступные модели:**
- `gemini-1.5-pro` - для сложных задач
- `gemini-1.5-flash` - для быстрых ответов
- `gemini-1.5-pro-latest` - последняя версия
- `gemini-pro` - стабильная версия
- `gemini-pro-vision` - для работы с изображениями

### 2. Мультимодальный анализ
**POST** `/api/gemini/multimodal`

**Параметры:**
```json
{
  "prompt": "Опиши что на этом изображении",
  "images": [
    "https://example.com/image.jpg",
    "/path/to/local/image.jpg"
  ],
  "model": "gemini-1.5-pro" // опционально
}
```

### 3. Streaming генерация
**POST** `/api/gemini/stream`

**Параметры:**
```json
{
  "prompt": "Расскажи историю",
  "model": "gemini-1.5-flash" // опционально
}
```

**Ответ:** Server-Sent Events (SSE) поток

### 4. Embeddings
**POST** `/api/gemini/embedding`

**Параметры:**
```json
{
  "text": "Текст для создания embedding"
}
```

### 5. Function Calling
**POST** `/api/gemini/functions`

**Параметры:**
```json
{
  "prompt": "Какая погода в Москве?",
  "functions": [
    {
      "name": "get_weather",
      "description": "Получить погоду",
      "parameters": {
        "type": "object",
        "properties": {
          "location": {"type": "string"}
        }
      }
    }
  ],
  "model": "gemini-1.5-pro" // опционально
}
```

### 6. JSON режим
**POST** `/api/gemini/json`

**Параметры:**
```json
{
  "prompt": "Создай JSON с данными пользователя",
  "model": "gemini-1.5-pro" // опционально
}
```

---

## OpenAI API

### 1. Chat Completions
**POST** `/api/openai/chat`

**Параметры:**
```json
{
  "messages": [
    {"role": "system", "content": "Ты помощник"},
    {"role": "user", "content": "Привет!"}
  ],
  "model": "gpt-4-turbo-preview", // опционально
  "temperature": 0.7, // опционально
  "max_tokens": 2000 // опционально
}
```

**Доступные модели:**
- `gpt-4-turbo-preview` - GPT-4 Turbo
- `gpt-4` - GPT-4 стабильная
- `gpt-4-vision-preview` - GPT-4 Vision
- `gpt-3.5-turbo` - GPT-3.5 Turbo
- `gpt-3.5-turbo-16k` - GPT-3.5 Turbo 16K

### 2. Vision - анализ изображений
**POST** `/api/openai/vision`

**Параметры:**
```json
{
  "prompt": "Что на этом изображении?",
  "images": [
    "https://example.com/image.jpg",
    "/path/to/local/image.jpg"
  ],
  "model": "gpt-4-vision-preview" // опционально
}
```

### 3. DALL-E - генерация изображений
**POST** `/api/openai/image`

**Параметры:**
```json
{
  "prompt": "Красивый закат над океаном",
  "size": "1024x1024", // 1024x1024, 1792x1024, 1024x1792
  "model": "dall-e-3", // опционально
  "n": 1 // количество изображений (1-10)
}
```

### 4. Whisper - транскрипция аудио
**POST** `/api/openai/transcribe`

**Параметры:**
- `audio` (file) - аудио файл (mp3, mp4, mpeg, mpga, m4a, wav, webm)
- `language` (string, опционально) - код языка (ru, en и т.д.)
- `model` (string, опционально) - whisper-1

### 5. Embeddings
**POST** `/api/openai/embedding`

**Параметры:**
```json
{
  "text": "Текст для создания embedding",
  "model": "text-embedding-3-small" // опционально
}
```

**Доступные модели:**
- `text-embedding-3-small` - 1536 размерность
- `text-embedding-3-large` - 3072 размерность
- `text-embedding-ada-002` - 1536 размерность

### 6. TTS - синтез речи
**POST** `/api/openai/tts`

**Параметры:**
```json
{
  "text": "Привет, это тестовое сообщение",
  "voice": "alloy", // alloy, echo, fable, onyx, nova, shimmer
  "model": "tts-1" // tts-1 или tts-1-hd
}
```

### 7. Moderation - модерация контента
**POST** `/api/openai/moderate`

**Параметры:**
```json
{
  "text": "Текст для проверки",
  "model": "text-moderation-latest" // опционально
}
```

### 8. Streaming chat
**POST** `/api/openai/stream`

**Параметры:**
```json
{
  "messages": [
    {"role": "user", "content": "Расскажи историю"}
  ],
  "model": "gpt-4-turbo-preview" // опционально
}
```

**Ответ:** Server-Sent Events (SSE) поток

### 9. Function Calling
**POST** `/api/openai/functions`

**Параметры:**
```json
{
  "messages": [
    {"role": "user", "content": "Какая погода?"}
  ],
  "functions": [
    {
      "name": "get_weather",
      "description": "Получить погоду",
      "parameters": {
        "type": "object",
        "properties": {
          "location": {"type": "string"}
        }
      }
    }
  ],
  "model": "gpt-4-turbo-preview" // опционально
}
```

### 10. JSON режим
**POST** `/api/openai/json`

**Параметры:**
```json
{
  "messages": [
    {"role": "user", "content": "Создай JSON с данными"}
  ],
  "model": "gpt-4-turbo-preview" // опционально
}
```

---

## Примеры использования

### cURL - Gemini генерация
```bash
curl -X POST https://api.siteaccess.ru/api/gemini/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "Напиши короткий рассказ",
    "model": "gemini-1.5-pro"
  }'
```

### cURL - OpenAI Chat
```bash
curl -X POST https://api.siteaccess.ru/api/openai/chat \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "messages": [
      {"role": "user", "content": "Привет!"}
    ],
    "model": "gpt-4-turbo-preview"
  }'
```

### JavaScript - Fetch
```javascript
const response = await fetch('https://api.siteaccess.ru/api/gemini/generate', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    prompt: 'Напиши рассказ',
    model: 'gemini-1.5-pro'
  })
});

const data = await response.json();
console.log(data);
```

### PHP - Guzzle
```php
$client = new \GuzzleHttp\Client();
$response = $client->post('https://api.siteaccess.ru/api/openai/chat', [
    'headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
        'Content-Type' => 'application/json'
    ],
    'json' => [
        'messages' => [
            ['role' => 'user', 'content' => 'Привет!']
        ],
        'model' => 'gpt-4-turbo-preview'
    ]
]);

$data = json_decode($response->getBody(), true);
```

---

## Коды ответов

- `200` - Успешный запрос
- `400` - Ошибка валидации
- `401` - Не авторизован
- `422` - Ошибка обработки запроса
- `500` - Внутренняя ошибка сервера

---

## Ограничения

- Все endpoints требуют аутентификации через Sanctum
- Максимальный размер файла для транскрипции: 25MB
- Максимальная длина текста для TTS: 4096 символов
- Rate limiting может применяться в зависимости от настроек
