# AL API Gateway

Unified API Gateway for accessing multiple AI providers (Gemini, OpenAI) with built-in key management, analytics, and rate limiting.

## 📚 Documentation

### Основная документация:

1. **[API_GATEWAY_DOCUMENTATION.md](API_GATEWAY_DOCUMENTATION.md)** ⭐
   - Полная документация API
   - Все endpoints с примерами
   - Форматы запросов и ответов
   - Примеры использования curl

2. **[SETUP_COMPLETE.md](SETUP_COMPLETE.md)**
   - Статус установки
   - Учетные данные
   - Быстрый старт
   - Примеры запросов

3. **[IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)**
   - Что реализовано
   - Структура проекта
   - Статистика

### Техническая документация:

4. **[ARCHITECTURE_PROPOSAL.md](ARCHITECTURE_PROPOSAL.md)**
   - Варианты архитектуры
   - Схемы работы
   - Рекомендации

5. **[IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md)**
   - План реализации
   - Структура БД
   - Конфигурация

6. **[PROMPT_LIBRARY_EXPLANATION.md](PROMPT_LIBRARY_EXPLANATION.md)**
   - Объяснение библиотеки промптов
   - Примеры использования

7. **[AI_MODELS_GUIDE.md](AI_MODELS_GUIDE.md)**
   - Справка по моделям AI
   - Gemini и OpenAI модели
   - Возможности каждой модели

8. **[SYNC_STATUS.md](SYNC_STATUS.md)**
   - Статус синхронизации Git
   - Команды для работы

## 🚀 Quick Start

### API Base URL
```
https://api.siteaccess.ru/api
```

### Authentication
All endpoints (except `/test`) require Sanctum Bearer token:
```
Authorization: Bearer {your_token}
```

### Test Endpoint
```bash
curl https://api.siteaccess.ru/api/test
```

### Main AI Endpoint
```bash
curl -X POST \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "provider": "gemini",
    "model": "gemini-1.5-pro",
    "prompt": "Hello AI!"
  }' \
  https://api.siteaccess.ru/api/ai/process
```

## 📖 Полная документация

Смотрите **[API_GATEWAY_DOCUMENTATION.md](API_GATEWAY_DOCUMENTATION.md)** для:
- Всех доступных endpoints
- Детального описания запросов
- Примеров использования
- Обработки ошибок
- Конфигурации

## 🔧 Setup

### Создать API токен
```bash
cd /var/www/AL
php create_api_token.php
```

### Запустить миграции
```bash
php artisan migrate
```

### Очистить кэш
```bash
php artisan config:cache
php artisan route:cache
```

## 📊 Features

- ✅ Multi-provider support (Gemini, OpenAI)
- ✅ API key management (internal/user-provided)
- ✅ Usage tracking and analytics
- ✅ Cost calculation
- ✅ Rate limiting
- ✅ Request logging
- ✅ File support (images, audio, documents)

## 🔗 Links

- **API**: https://api.siteaccess.ru/api
- **GitHub**: https://github.com/letoceiling-coder/al-api
- **Test**: https://api.siteaccess.ru/api/test

## 📝 License

Private project
