# 🔍 Проверка парсера на сервере

## Команды для проверки на сервере

### 1. Проверить, что методы реализованы

```bash
cd /var/www/AL

# Проверить parseParkings
grep -A 20 "protected function parseParkings" app/Console/Commands/TrendAgentParse.php | head -25

# Проверить parseHouses
grep -A 20 "protected function parseHouses" app/Console/Commands/TrendAgentParse.php | head -25

# Проверить parsePlots
grep -A 20 "protected function parsePlots" app/Console/Commands/TrendAgentParse.php | head -25

# Проверить parseCommercial
grep -A 20 "protected function parseCommercial" app/Console/Commands/TrendAgentParse.php | head -25
```

### 2. Проверить, что НЕТ строки "not yet implemented"

```bash
# Должно быть пусто (ничего не найдено)
grep -n "not yet implemented" app/Console/Commands/TrendAgentParse.php
```

Если что-то найдено - файл не обновлен!

### 3. Проверить, что используется TrendAgentApiClient

```bash
# Должно найти использование TrendAgentApiClient
grep -n "TrendAgentApiClient" app/Console/Commands/TrendAgentParse.php | head -10
```

### 4. Проверить версию файла в Git

```bash
# Посмотреть последние изменения файла
git log --oneline -5 -- app/Console/Commands/TrendAgentParse.php

# Посмотреть текущую версию
git show HEAD:app/Console/Commands/TrendAgentParse.php | grep -A 5 "parseParkings" | head -10
```

### 5. Если все проверки пройдены, но парсер не работает

Попробовать перезапустить PHP-FPM или веб-сервер:

```bash
# Перезапустить PHP-FPM
systemctl restart php8.1-fpm
# или
service php8.1-fpm restart

# Или перезапустить веб-сервер
systemctl restart nginx
# или
service nginx restart
```

### 6. Проверить автозагрузку классов

```bash
# Перегенерировать автозагрузку
composer dump-autoload

# Очистить все кеши
php artisan optimize:clear
php artisan clear-compiled
```

### 7. Тестовый запуск одного типа

```bash
# Запустить только паркинги с лимитом 5
php artisan trendagent:parse --region=spb --type=parkings --limit=5 --details --save-raw
```

Должно вывести:
```
Fetching parkings list...
  Parsed parking: ... (1)
  Parsed parking: ... (2)
  ...
```

А НЕ:
```
Parkings parsing not yet implemented
```
