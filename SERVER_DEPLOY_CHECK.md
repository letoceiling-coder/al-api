# 🔍 Проверка и развертывание на сервере

## 📋 Шаги для проверки и развертывания

### 1. Подключиться к серверу

```bash
ssh root@89.169.39.244
```

### 2. Перейти в директорию проекта

```bash
cd /var/www/AL
```

### 3. Проверить статус Git

```bash
git status
git log --oneline -5
```

### 4. Выполнить развертывание

```bash
php artisan deploy:trendagent
```

Команда автоматически:
- ✅ Обновит проект из Git
- ✅ Установит зависимости Composer
- ✅ Установит зависимости NPM (включая @vitejs/plugin-react)
- ✅ Соберет проект (`npm run build`)
- ✅ Выполнит миграции
- ✅ Очистит кеш

### 5. Проверить файлы фронтенда

```bash
ls -la /var/www/AL/public/trendagent/
ls -la /var/www/AL/public/trendagent/assets/
```

Должны быть файлы:
- `index.html`
- `assets/index-*.js`
- `assets/index-*.css`
- `assets/react-vendor-*.js`
- `assets/axios-vendor-*.js`

### 6. Проверить права доступа

```bash
chown -R www-data:www-data /var/www/AL/public/trendagent
chmod -R 755 /var/www/AL/public/trendagent
```

### 7. Очистить кеш браузера

В браузере:
- Нажмите `Ctrl+Shift+R` (Windows/Linux) или `Cmd+Shift+R` (Mac)
- Или откройте в режиме инкогнито
- Или очистите кеш вручную

### 8. Проверить в браузере

Откройте: `https://api.siteaccess.ru/trendagent/parser`

Должна появиться кнопка **"🚀 Полный парсинг + Анализ"**

---

## 🐛 Если кнопка все еще не видна

### Проверка 1: Файлы собраны?

```bash
cd /var/www/AL/projects/trendagent
cat public/trendagent/index.html | grep "Полный парсинг"
```

Если команда ничего не выводит, значит файлы не собраны или устарели.

### Проверка 2: Содержимое index.html

```bash
cat /var/www/AL/public/trendagent/index.html
```

Проверьте, что в файле есть ссылки на правильные JS файлы.

### Проверка 3: Ручная сборка

```bash
cd /var/www/AL/projects/trendagent
npm install
npm run build
```

### Проверка 4: Проверить консоль браузера

Откройте DevTools (F12) → Console и проверьте ошибки JavaScript.

### Проверка 5: Проверить Network

Откройте DevTools (F12) → Network и проверьте, загружаются ли JS файлы (статус 200).

---

## ✅ Быстрая команда для всего

```bash
cd /var/www/AL && \
php artisan deploy:trendagent && \
chown -R www-data:www-data public/trendagent && \
chmod -R 755 public/trendagent && \
echo "✅ Развертывание завершено!"
```

---

## 📝 Примечания

- Команда `deploy:trendagent` автоматически определяет, что она запущена на сервере
- Все зависимости будут установлены автоматически
- После сборки файлы будут в `public/trendagent/`
- Не забудьте очистить кеш браузера!
