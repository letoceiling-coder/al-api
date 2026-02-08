# 🚀 Инструкция по развертыванию на сервере

## Проблема: Кнопка "Полный парсинг + Анализ" не отображается

### ✅ Решение:

**На сервере выполните:**

```bash
# 1. Подключиться к серверу
ssh root@89.169.39.244

# 2. Перейти в директорию проекта
cd /var/www/AL

# 3. Выполнить развертывание (автоматически обновит из Git, установит зависимости, соберет проект)
php artisan deploy:trendagent

# 4. Проверить права доступа
chown -R www-data:www-data public/trendagent
chmod -R 755 public/trendagent

# 5. Проверить, что файлы собраны
ls -la public/trendagent/assets/
```

**Должны быть файлы:**
- `index-BtU4Sbb5.js`
- `index-DXWCK6sL.css`
- `react-vendor-DdVQdU_w.js`
- `axios-vendor-D5GkNzM3.js`

### 🔍 Проверка в браузере:

1. **Очистите кеш браузера:**
   - Нажмите `Ctrl+Shift+R` (Windows/Linux) или `Cmd+Shift+R` (Mac)
   - Или откройте в режиме инкогнито

2. **Откройте:** `https://api.siteaccess.ru/trendagent/parser`

3. **Проверьте консоль браузера (F12 → Console):**
   - Не должно быть ошибок 404 для JS файлов
   - Не должно быть ошибок JavaScript

### 🐛 Если кнопка все еще не видна:

**Проверка 1: Файлы на сервере**
```bash
# На сервере
cat /var/www/AL/public/trendagent/index.html | grep "v=3"
```

**Проверка 2: Ручная сборка на сервере**
```bash
cd /var/www/AL/projects/trendagent
npm install
npm run build
```

**Проверка 3: Проверить содержимое JS файла**
```bash
# На сервере
grep -o "Полный парсинг" /var/www/AL/public/trendagent/assets/index-*.js
```

Если команда ничего не выводит, значит файлы не собраны или устарели.

---

## 📝 Что делает команда `deploy:trendagent`:

1. ✅ Обновляет проект из Git (`git pull`)
2. ✅ Устанавливает зависимости Composer (`composer install`)
3. ✅ Устанавливает зависимости NPM (`npm install`)
4. ✅ Собирает проект (`npm run build`)
5. ✅ Выполняет миграции (`php artisan migrate`)
6. ✅ Очищает кеш Laravel

---

**Важно:** После развертывания обязательно очистите кеш браузера!
