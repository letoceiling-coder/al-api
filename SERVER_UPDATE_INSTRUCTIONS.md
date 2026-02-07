# Инструкции по обновлению файлов на сервере

**Дата:** 2026-02-07  
**Коммит:** `efe593c` - Fix: Исправлен роут для детальной страницы квартиры, добавлено кэширование на 60 минут, создана Swagger документация

---

## Быстрое обновление

### Вариант 1: Использование скрипта (рекомендуется)

```bash
# Подключиться к серверу
ssh root@89.169.39.244

# Перейти в директорию проекта
cd /var/www/AL

# Скачать скрипт обновления (если его нет)
# Или выполнить команды вручную:

# 1. Получить изменения из GitHub
git pull origin main

# 2. Очистить кэш
php artisan route:clear
php artisan config:clear
php artisan view:clear

# 3. Обновить зависимости (если нужно)
composer install --no-dev --optimize-autoloader

# 4. Пересобрать кэш
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Обновление завершено!"
```

### Вариант 2: Ручное выполнение команд

```bash
# 1. Подключиться к серверу
ssh root@89.169.39.244

# 2. Перейти в директорию проекта
cd /var/www/AL

# 3. Проверить текущий статус
git status

# 4. Получить последние изменения
git pull origin main

# 5. Очистить все кэши
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 6. Обновить зависимости (если composer.json изменился)
composer install --no-dev --optimize-autoloader

# 7. Пересобрать кэш для производительности
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Проверить, что роуты зарегистрированы правильно
php artisan route:list --path=trendagent/apartments/flat
```

---

## Что было изменено

### 1. Исправлен роут для детальной страницы квартиры
- Изменен порядок роутов в `routes/trendagent.php`
- Более специфичный роут `/{id}/flat/{apartmentId}` теперь определен раньше `/{id}`
- Добавлены ограничения на параметры роутов

### 2. Добавлено кэширование на 60 минут
- Кэширование для методов в `TrendSsoApiAuth.php`:
  - `getBlocksSearch()`
  - `getBlockFullData()`
  - `getBlockApartments()`
  - `getBlockPlans()`
  - `getCheckerboardBuildings()`
  - `getCheckerboardApartments()`

### 3. Создана Swagger документация
- Добавлен контроллер `TrendAgentSwaggerController.php`
- Добавлены роуты для Swagger UI
- Создана полная документация API в `TRENDAGENT_API_DOCUMENTATION.md`

---

## Проверка после обновления

### 1. Проверить роуты
```bash
php artisan route:list --path=trendagent/apartments
```

Должен быть виден роут:
```
POST api/trendagent/apartments/{id}/flat/{apartmentId} ... TrendAgent\ApartmentsController@flatDetail
```

### 2. Проверить работу API
```bash
# Проверить доступность Swagger
curl https://api.siteaccess.ru/trendagent/swagger.json

# Проверить роут для детальной страницы квартиры
curl -X POST https://api.siteaccess.ru/api/trendagent/apartments/63c50acc9a85d53360f63a76/flat/63c5614728d3bcf2420860b1 \
  -H "Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF" \
  -H "Content-Type: application/json" \
  -d '{"phone":"+79045393434","password":"nwBvh4q"}'
```

### 3. Проверить кэширование
```bash
# Проверить, что кэш работает
php artisan tinker
>>> Cache::get('trendagent:blocks_search:*');
```

---

## Возможные проблемы

### Проблема 1: Конфликт при git pull
```bash
# Если есть конфликты, можно сделать hard reset
git fetch origin
git reset --hard origin/main
```

### Проблема 2: Ошибки при очистке кэша
```bash
# Если есть проблемы с БД для кэша, можно очистить файловый кэш
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*
```

### Проблема 3: Роут все еще не работает
```bash
# Убедиться, что роут зарегистрирован
php artisan route:clear
php artisan route:cache
php artisan route:list --path=trendagent/apartments
```

---

## Дополнительные команды

### Очистка всех кэшей
```bash
php artisan optimize:clear
```

### Пересборка всех кэшей
```bash
php artisan optimize
```

### Проверка конфигурации
```bash
php artisan config:show
```

---

## Контакты

При возникновении проблем:
1. Проверить логи: `tail -f storage/logs/laravel.log`
2. Проверить права доступа: `ls -la storage/ bootstrap/cache/`
3. Проверить версию PHP: `php -v` (должна быть >= 8.1)

---

**Статус:** ✅ Готово к обновлению
