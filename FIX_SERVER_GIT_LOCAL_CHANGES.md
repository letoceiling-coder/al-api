# 🔧 Исправление локальных изменений на сервере

## Проблема

На сервере есть локальные изменения в собранных файлах фронтенда, которые мешают обновлению.

## ✅ Решение

**Выполните на сервере:**

```bash
cd /var/www/AL

# Вариант 1: Откатить изменения в собранных файлах (рекомендуется)
# Эти файлы будут пересобраны командой deploy:trendagent
git checkout -- public/trendagent/assets/index-BehUVRzs.css
git checkout -- public/trendagent/assets/index-lRqIAHwd.js
git checkout -- public/trendagent/index.html

# Теперь обновить из Git
git pull

# Проверить файл
ls -la app/Console/Commands/DeployTrendagentCommand.php

# Очистить кеш
php artisan clear-compiled
php artisan config:clear
php artisan cache:clear

# Выполнить развертывание (это пересоберет фронтенд)
php artisan deploy:trendagent
```

---

## 🔄 Альтернативный вариант (сохранить изменения)

Если нужно сохранить изменения:

```bash
cd /var/www/AL

# Сохранить изменения в stash
git stash

# Обновить из Git
git pull

# Проверить файл
ls -la app/Console/Commands/DeployTrendagentCommand.php

# Очистить кеш
php artisan clear-compiled
php artisan config:clear
php artisan cache:clear

# Выполнить развертывание (пересоберет фронтенд с правильными файлами)
php artisan deploy:trendagent
```

---

## ⚠️ Важно

Собранные файлы фронтенда (`public/trendagent/assets/*` и `public/trendagent/index.html`) будут автоматически пересобраны командой `deploy:trendagent`, поэтому их можно безопасно откатить.

После `git pull` файл `DeployTrendagentCommand.php` появится, и команда заработает.
