# 🔧 Исправление развертывания на сервере

## Проблема

`git pull` не выполнил слияние, потому что не настроено отслеживание ветки. Файл `DeployTrendagentCommand.php` есть в Git, но не появился на сервере.

## ✅ Решение

**Выполните на сервере следующие команды:**

```bash
cd /var/www/AL

# 1. Настроить отслеживание ветки
git branch --set-upstream-to=origin/main main

# 2. Обновить из Git (теперь правильно)
git pull

# 3. Проверить, что файл появился
ls -la app/Console/Commands/DeployTrendagentCommand.php

# 4. Очистить кеш Laravel
php artisan clear-compiled
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 5. Проверить команду
php artisan list | grep deploy

# 6. Выполнить развертывание
php artisan deploy:trendagent

# 7. Проверить права доступа
chown -R www-data:www-data public/trendagent
chmod -R 755 public/trendagent
```

---

## 🔍 Альтернативный вариант (если не помогло)

Если файл все еще не появился:

```bash
cd /var/www/AL

# Принудительно обновить из origin/main
git fetch origin
git reset --hard origin/main

# Очистить кеш
php artisan clear-compiled
php artisan config:clear
php artisan cache:clear

# Проверить файл
ls -la app/Console/Commands/DeployTrendagentCommand.php

# Если файл есть, выполнить развертывание
php artisan deploy:trendagent
```

---

## 📝 Проверка после исправления

После выполнения команд проверьте:

1. **Файл существует:**
   ```bash
   ls -la app/Console/Commands/DeployTrendagentCommand.php
   ```

2. **Команда видна:**
   ```bash
   php artisan list | grep deploy
   ```

3. **Фронтенд собран:**
   ```bash
   ls -la public/trendagent/assets/
   ```

4. **В браузере:**
   - Откройте: `https://api.siteaccess.ru/trendagent/parser`
   - Очистите кеш: `Ctrl+Shift+R`
   - Проверьте кнопку "🚀 Полный парсинг + Анализ"

---

**Важно:** После `git pull` файл должен появиться, так как он точно есть в коммите `d6565fc`.
