# 🔧 Исправление проблемы с кешем

## Проблема

Парсер пытается использовать кеш из MySQL, но MySQL недоступен:
```
SQLSTATE[HY000] [2002] Подключение не установлено, т.к. конечный компьютер отверг запрос на подключение (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: al_db, SQL: select * from `cache` where `key` in (laravel-cache-trendagent:blocks_search:...))
```

## Решение

### 1. Изменен драйвер кеша по умолчанию

**Файл:** `config/cache.php`

```php
'default' => env('CACHE_STORE', env('CACHE_DRIVER', 'file')),
```

Теперь по умолчанию используется файловый кеш вместо database.

### 2. Добавлена обработка ошибок кеша

**Файл:** `app/Services/TrendAgent/TrendSsoApiAuth.php`

Метод `getBlocksSearch()` теперь обернут в try-catch:
- Если кеш недоступен, запрос выполняется напрямую без кеша
- Логируется предупреждение, но парсер продолжает работать

### 3. Альтернативное решение (если нужно)

Если нужно использовать файловый кеш, добавьте в `.env`:

```env
CACHE_DRIVER=file
CACHE_STORE=file
```

Или для отключения кеша:

```env
CACHE_DRIVER=array
CACHE_STORE=array
```

---

## Проверка

После исправления парсер должен работать даже если MySQL недоступен:

```bash
php artisan trendagent:parse --region=spb --type=all --details --save-raw
```

Кеш будет работать через файловую систему, не требуя MySQL.
