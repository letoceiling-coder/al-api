# 🔧 Исправление проблем парсера на сервере

## Проблемы

1. **HTTP 405** - неправильный метод запроса для complexes
2. **Permission denied** - нет прав на запись в `storage/trendagent/parsing/spb/metadata/statistics.json`

## ✅ Исправления в коде

Исправлены обе проблемы:

1. **HTTP 405** - теперь используется `TrendAgentApiClient` вместо прямых HTTP запросов
2. **Permission denied** - добавлена проверка прав и fallback на Storage facade

## 🔧 Дополнительно на сервере

**Выполните на сервере для исправления прав доступа:**

```bash
cd /var/www/AL

# Установить правильные права на директорию storage
chown -R www-data:www-data storage/trendagent
chmod -R 775 storage/trendagent

# Создать директорию metadata если её нет
mkdir -p storage/trendagent/parsing/spb/metadata
chown -R www-data:www-data storage/trendagent/parsing/spb/metadata
chmod -R 775 storage/trendagent/parsing/spb/metadata

# Проверить права
ls -la storage/trendagent/parsing/spb/metadata/
```

## 📝 Что исправлено

1. ✅ `fetchObjectsList()` - теперь использует `TrendAgentApiClient` вместо прямых HTTP запросов
2. ✅ `saveStatistics()` - добавлена проверка прав и fallback на Storage facade
3. ✅ `saveRawData()` - добавлена проверка прав и fallback на Storage facade
4. ✅ `saveDetailsData()` - добавлена проверка прав и fallback на Storage facade
5. ✅ `logError()` - добавлена проверка прав и fallback на Storage facade

## 🚀 После исправления

1. Обновите код на сервере: `git pull`
2. Установите права доступа (команды выше)
3. Попробуйте запустить парсер снова

**Все исправления готовы к отправке в Git!**
