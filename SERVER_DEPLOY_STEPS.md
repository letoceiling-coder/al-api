# 🚀 Пошаговая инструкция для развертывания на сервере

## ✅ Изменения отправлены в Git!

Теперь на сервере выполните:

### 1. Обновить проект из Git

```bash
cd /var/www/AL
git pull
```

### 2. Выполнить развертывание

```bash
php artisan deploy:trendagent
```

Команда автоматически:
- ✅ Установит зависимости Composer
- ✅ Установит зависимости NPM (включая @vitejs/plugin-react)
- ✅ Соберет проект (`npm run build`)
- ✅ Добавит версию к assets (для обхода кеша)
- ✅ Выполнит миграции
- ✅ Очистит кеш Laravel

### 3. Проверить права доступа

```bash
chown -R www-data:www-data public/trendagent
chmod -R 755 public/trendagent
```

### 4. Проверить файлы

```bash
ls -la public/trendagent/assets/
cat public/trendagent/index.html | grep "v="
```

Должны быть файлы:
- `index-BtU4Sbb5.js`
- `index-DXWCK6sL.css`
- `react-vendor-DdVQdU_w.js`
- `axios-vendor-D5GkNzM3.js`

И в `index.html` должны быть версии типа `?v=1234567890`

---

## 🔍 Если команда все еще не найдена

Если после `git pull` команда `deploy:trendagent` все еще не найдена:

```bash
# Очистить кеш Laravel
php artisan clear-compiled
php artisan config:clear
php artisan cache:clear

# Проверить, что файл существует
ls -la app/Console/Commands/DeployTrendagentCommand.php

# Если файла нет, проверить git статус
git status
git log --oneline -5
```

---

## ✅ После развертывания

1. **Очистите кеш браузера:** `Ctrl+Shift+R` или откройте в режиме инкогнито
2. **Откройте:** `https://api.siteaccess.ru/trendagent/parser`
3. **Проверьте консоль браузера (F12 → Console)** на наличие ошибок

Кнопка **"🚀 Полный парсинг + Анализ"** должна появиться!
