# Исправление проблем деплоя

## Проблема 1: Конфликт роутов Swagger

**Ошибка:**
```
LogicException: Unable to prepare route [docs/{jsonFile?}] for serialization. 
Another route has already been assigned name [l5-swagger.default.docs].
```

**Причина:** В `routes/web.php` был дублирующий роут с именем `l5-swagger.default.docs`, который конфликтовал с автоматически регистрируемым роутом l5-swagger.

**Решение:** Удален дублирующий роут из `routes/web.php`, так как l5-swagger автоматически регистрирует этот роут.

## Проблема 2: Роут не найден

**Ошибка:**
```
ERROR  Your application doesn't have any routes matching the given criteria.
```

**Причина:** Возможно, роут не зарегистрирован из-за ошибки при кэшировании или нужно проверить без фильтра.

**Решение:** После исправления конфликта роутов нужно:
1. Очистить кэш роутов
2. Проверить роуты без фильтра
3. Пересобрать кэш

## Команды для выполнения на сервере:

```bash
cd /var/www/AL

# 1. Получить исправление
git pull origin main

# 2. Очистить кэш
php artisan route:clear
php artisan config:clear

# 3. Проверить роуты (без фильтра, чтобы увидеть все)
php artisan route:list | grep "flat"

# 4. Если роут есть, пересобрать кэш
php artisan route:cache
php artisan config:cache

# 5. Проверить снова
php artisan route:list --path=trendagent/apartments
```

## Альтернатива: Не кэшировать роуты

Если проблемы с кэшированием продолжаются, можно не кэшировать роуты:

```bash
php artisan route:clear
php artisan config:cache
# НЕ выполнять: php artisan route:cache
```

Это немного замедлит работу, но устранит проблемы с конфликтами роутов.
