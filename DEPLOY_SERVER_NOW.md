# 🚀 СРОЧНО: Развертывание на сервере

## ⚡ Быстрая команда (скопируйте и выполните на сервере):

```bash
ssh root@89.169.39.244
cd /var/www/AL
php artisan deploy:trendagent
chown -R www-data:www-data public/trendagent
chmod -R 755 public/trendagent
```

## ✅ Что делает команда:

1. ✅ Обновляет проект из Git
2. ✅ Устанавливает зависимости Composer
3. ✅ Устанавливает зависимости NPM (включая @vitejs/plugin-react)
4. ✅ Собирает проект (`npm run build`)
5. ✅ **Автоматически добавляет версию к assets** (для обхода кеша браузера)
6. ✅ Выполняет миграции
7. ✅ Очищает кеш Laravel

## 🔍 После развертывания:

1. **Очистите кеш браузера:**
   - Нажмите `Ctrl+Shift+R` (Windows/Linux) или `Cmd+Shift+R` (Mac)
   - Или откройте в режиме инкогнито

2. **Откройте:** `https://api.siteaccess.ru/trendagent/parser`

3. **Проверьте консоль браузера (F12 → Console):**
   - Не должно быть ошибок 404
   - Не должно быть ошибок JavaScript

## 🐛 Если кнопка все еще не видна:

**Проверьте на сервере:**
```bash
# Проверить, что файлы собраны
ls -la /var/www/AL/public/trendagent/assets/

# Проверить index.html
cat /var/www/AL/public/trendagent/index.html | grep "v="

# Если версии нет, пересобрать вручную
cd /var/www/AL/projects/trendagent
npm run build
cd /var/www/AL
php artisan deploy:trendagent --skip-git --skip-migrations --skip-cache
```

---

**Важно:** Команда `deploy:trendagent` теперь автоматически добавляет версию к assets после каждой сборки!
