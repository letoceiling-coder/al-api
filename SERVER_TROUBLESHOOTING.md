# 🔍 Диагностика проблемы на сервере

## Проблема

После `git pull` парсер все еще показывает:
- ❌ HTTP 405 для apartments
- ❌ "Parkings parsing not yet implemented"
- ❌ "Houses parsing not yet implemented"
- ❌ "Plots parsing not yet implemented"
- ❌ "Commercial parsing not yet implemented"

## Возможные причины

### 1. Git pull не обновил файл

Проверьте на сервере:

```bash
cd /var/www/AL

# Проверить статус Git
git status

# Проверить последние коммиты
git log --oneline -5

# Проверить, что файл обновлен
grep -n "Parkings parsing not yet implemented" app/Console/Commands/TrendAgentParse.php
```

Если строка найдена - файл не обновлен!

### 2. Конфликт двух команд

Возможно, есть две команды с одинаковым signature:
- `app/Console/Commands/TrendAgentParse.php` (старая)
- `app/Console/Commands/TrendAgent/ParseCommand.php` (новая)

Laravel использует последнюю зарегистрированную. Нужно проверить:

```bash
# Найти все команды с trendagent:parse
grep -r "trendagent:parse" app/Console/Commands/

# Проверить, какая команда зарегистрирована
php artisan list | grep trendagent
```

### 3. Кеш команд Laravel

Очистите кеш команд:

```bash
php artisan clear-compiled
php artisan optimize:clear
```

### 4. Проверить версию файла на сервере

```bash
# Проверить, что метод parseParkings реализован
grep -A 20 "protected function parseParkings" app/Console/Commands/TrendAgentParse.php

# Должно быть что-то вроде:
# protected function parseParkings(): void
# {
#     $offset = (int) $this->option('offset');
#     ...
#     $apiClient = new \App\Services\TrendAgent\TrendAgentApiClient();
#     ...
# }
```

Если видите "Parkings parsing not yet implemented" - файл старый!

## Решение

### Вариант 1: Принудительное обновление

```bash
cd /var/www/AL

# Откатить локальные изменения (если есть)
git reset --hard HEAD

# Обновить из Git
git pull origin main

# Очистить все кеши
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan clear-compiled
```

### Вариант 2: Удалить старую команду

Если есть две команды, удалите старую:

```bash
# Переименовать старую команду (чтобы не конфликтовала)
mv app/Console/Commands/TrendAgentParse.php app/Console/Commands/TrendAgentParse.php.old

# Очистить кеш
php artisan optimize:clear
```

### Вариант 3: Проверить, что изменения в Git

```bash
# На локальной машине проверить, что файл закоммичен
git log --oneline --all -- app/Console/Commands/TrendAgentParse.php

# На сервере проверить, что файл обновлен
git log --oneline -- app/Console/Commands/TrendAgentParse.php
```

## Проверка после исправления

После обновления должно работать:

```bash
php artisan trendagent:parse --region=spb --type=parkings --limit=10
```

Должно вывести:
```
Fetching parkings list...
  Parsed parking: ... (1)
  Parsed parking: ... (2)
  ...
```

А не:
```
Parkings parsing not yet implemented
```
