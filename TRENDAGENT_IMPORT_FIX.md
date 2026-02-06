# Исправление ошибки импорта TrendAgent API

**Дата:** 2026-02-06  
**Проблема:** `TypeError: y.authenticate is not a function`

## Проблема

В React приложении возникала ошибка при попытке вызвать `trendAgentAPI.authenticate()`, так как использовался неправильный импорт.

### Ошибка:
```javascript
// Неправильно - default import
import trendAgentAPI from '../services/api'
```

В файле `api.js` экспортируется:
```javascript
export const trendAgentAPI = { ... }  // именованный экспорт
export default apiClient              // default export - это apiClient, а не trendAgentAPI
```

## Решение

Исправлен импорт во всех файлах, использующих `trendAgentAPI`:

### Исправленные файлы:

1. **`src/pages/ObjectsList.jsx`**
   ```javascript
   // Было:
   import trendAgentAPI from '../services/api'
   
   // Стало:
   import { trendAgentAPI } from '../services/api'
   ```

2. **`src/pages/ObjectDetail.jsx`**
   ```javascript
   // Было:
   import trendAgentAPI from '../services/api'
   
   // Стало:
   import { trendAgentAPI } from '../services/api'
   ```

3. **`src/components/SearchFilters.jsx`**
   ```javascript
   // Было:
   import trendAgentAPI from '../services/api'
   
   // Стало:
   import { trendAgentAPI } from '../services/api'
   ```

## Результат

✅ Ошибка исправлена  
✅ Приложение пересобрано  
✅ Файлы обновлены на сервере  
✅ Приложение работает корректно

## Проверка

После исправления приложение должно:
- ✅ Успешно авторизоваться через Trend SSO
- ✅ Загружать список городов
- ✅ Загружать список объектов
- ✅ Отображать детальную информацию об объектах

**URL:** https://api.siteaccess.ru/trendagent
