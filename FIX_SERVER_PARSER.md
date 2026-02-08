# 🔧 Исправление парсера на сервере

## Проблема

На сервере используется старая версия команды `TrendAgentParse.php`, которая показывает:
- ❌ "Parkings parsing not yet implemented"
- ❌ "Houses parsing not yet implemented"
- ❌ "Plots parsing not yet implemented"
- ❌ "Commercial parsing not yet implemented"

## Причина

Есть **ДВЕ команды** с одинаковым signature `trendagent:parse`:
1. `app/Console/Commands/TrendAgentParse.php` - старая (используется на сервере)
2. `app/Console/Commands/TrendAgent/ParseCommand.php` - новая (полная реализация)

Laravel использует последнюю зарегистрированную команду. Нужно либо удалить старую, либо обновить её.

## Решение на сервере

### Вариант 1: Проверить и обновить файл

```bash
cd /var/www/AL

# 1. Проверить, что файл обновлен
grep -n "Parkings parsing not yet implemented" app/Console/Commands/TrendAgentParse.php

# Если строка найдена - файл НЕ обновлен!
# Нужно принудительно обновить:

git fetch origin
git reset --hard origin/main
git pull

# 2. Очистить все кеши
php artisan optimize:clear
php artisan clear-compiled
php artisan config:clear
```

### Вариант 2: Удалить старую команду (рекомендуется)

```bash
cd /var/www/AL

# Переименовать старую команду (чтобы не конфликтовала)
mv app/Console/Commands/TrendAgentParse.php app/Console/Commands/TrendAgentParse.php.old

# Очистить кеш
php artisan optimize:clear
php artisan clear-compiled

# Проверить команды
php artisan list | grep trendagent
```

### Вариант 3: Проверить версию файла

```bash
cd /var/www/AL

# Проверить, что метод parseParkings реализован
grep -A 5 "protected function parseParkings" app/Console/Commands/TrendAgentParse.php

# Должно быть:
# protected function parseParkings(): void
# {
#     $offset = (int) $this->option('offset');
#     $limit = (int) $this->option('limit');
#     $count = 100;
#     $parsed = 0;
#     
#     $this->info("Fetching parkings list...");
#     
#     try {
#         $apiClient = new \App\Services\TrendAgent\TrendAgentApiClient();

# Если видите "Parkings parsing not yet implemented" - файл старый!
```

## Проверка после исправления

```bash
# Запустить парсер для одного типа
php artisan trendagent:parse --region=spb --type=parkings --limit=5

# Должно вывести:
# Fetching parkings list...
#   Parsed parking: ... (1)
#   Parsed parking: ... (2)
# ...

# А НЕ:
# Parkings parsing not yet implemented
```

## Если ничего не помогает

1. Проверить Git статус:
```bash
git status
git log --oneline -5
```

2. Принудительно обновить:
```bash
git fetch origin
git reset --hard origin/main
```

3. Очистить все:
```bash
php artisan optimize:clear
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/*
```
