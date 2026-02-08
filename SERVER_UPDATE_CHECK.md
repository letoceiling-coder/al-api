# Проверка обновления на сервере

## Проблема

Данные все еще неверные после развертывания. Возможные причины:
1. Файлы не обновились на сервере
2. Кеш Laravel не очищен
3. Автозагрузчик классов не обновлен

## Решение

Выполните на сервере следующие команды:

```bash
# 1. Перейти в директорию проекта
cd /var/www/AL

# 2. Принудительно обновить из Git
git fetch origin
git reset --hard origin/main

# 3. Обновить автозагрузчик классов
composer dump-autoload

# 4. Очистить все кеши Laravel
php artisan optimize:clear
php artisan clear-compiled
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Проверить, что файлы обновились
ls -la app/Services/TrendAgent/TrendSsoApiAuth.php
ls -la app/Services/TrendAgent/TrendAgentApiClient.php
ls -la app/Console/Commands/TrendAgentParse.php

# 6. Проверить версию файлов (дата изменения)
stat app/Services/TrendAgent/TrendSsoApiAuth.php | grep Modify
stat app/Services/TrendAgent/TrendAgentApiClient.php | grep Modify
stat app/Console/Commands/TrendAgentParse.php | grep Modify
```

## Проверка исправлений

После обновления проверьте, что методы используют правильные эндпоинты:

1. **Комплексы**: `getBlocksSearch()` должен возвращать `total` из `blocksCount`
2. **Квартиры**: `getApartmentsSearch()` использует `/v4_29/apartments/search/`
3. **Паркинги**: `getParkingPlacesSearch()` использует `parkings-api.trendagent.ru/search/places/`
4. **Дома**: `getApartmentsSearch()` с параметром `room=[30,40]`
5. **Участки**: `getVillagesSearch()` использует `house-api.trendagent.ru/v1/search/villages`
6. **Коммерция**: `getCommercePremisesSearch()` использует `commerce-api.trendagent.ru/search/premises`
7. **Подрядчики**: `getContractorsSearch()` использует `house-api.trendagent.ru/v1/projects/search`

## После обновления

Запустите парсер заново:
```bash
php artisan trendagent:parse --region=spb --type=all --details --save-raw
```

Проверьте таблицу с точными данными - она должна показывать правильные значения.
