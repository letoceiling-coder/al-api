# 🔧 Исправление проблемы с git pull на сервере

## Проблема

Git не может выполнить слияние, потому что на сервере есть неотслеживаемые файлы, которые будут перезаписаны.

## ✅ Решение

**Выполните на сервере:**

```bash
cd /var/www/AL

# Вариант 1: Удалить мешающие файлы (если они не нужны)
rm -f analyze_apartments_api.php
rm -f analyze_houses_api.php
rm -f analyze_parkings_api.php
rm -f analyze_remaining_types.php
rm -f app/Console/Commands/TrendAgent/TestApiEndpoints.php
rm -f app/Console/Commands/TrendAgent/TestRegionFilter.php
rm -f count_parsed_objects.php
rm -f test_two_ways_api.php

# Или Вариант 2: Переместить их в backup (если нужны)
# mkdir -p /tmp/server_backup
# mv analyze_*.php /tmp/server_backup/
# mv app/Console/Commands/TrendAgent/Test*.php /tmp/server_backup/
# mv count_parsed_objects.php /tmp/server_backup/
# mv test_two_ways_api.php /tmp/server_backup/

# Теперь обновить из Git
git pull

# Проверить файл
ls -la app/Console/Commands/DeployTrendagentCommand.php

# Очистить кеш
php artisan clear-compiled
php artisan config:clear
php artisan cache:clear

# Выполнить развертывание
php artisan deploy:trendagent
```

---

## 🔄 Альтернативный вариант (принудительное обновление)

Если файлы нужны, можно использовать принудительное обновление:

```bash
cd /var/www/AL

# Сохранить текущие изменения в stash
git stash

# Принудительно обновить из origin/main
git fetch origin
git reset --hard origin/main

# Очистить кеш
php artisan clear-compiled
php artisan config:clear
php artisan cache:clear

# Проверить файл
ls -la app/Console/Commands/DeployTrendagentCommand.php

# Выполнить развертывание
php artisan deploy:trendagent
```

---

## ⚠️ Важно

Эти файлы (`analyze_*.php`, `test_*.php`, `count_*.php`) похоже на тестовые/временные скрипты. Если они не нужны для production, их можно безопасно удалить.

После удаления/перемещения этих файлов `git pull` должен пройти успешно, и файл `DeployTrendagentCommand.php` появится.
