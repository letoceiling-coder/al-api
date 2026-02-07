# 🚀 Быстрый деплой на сервер

## Вариант 1: Выполнить одну команду (скопировать и вставить)

```bash
ssh root@89.169.39.244 "cd /var/www/AL && git stash || true && rm -f public/trendagent/assets/index-CkKWxhNq.css public/trendagent/assets/index-CzMGkjVf.js && git fetch origin && git reset --hard origin/main && php artisan route:clear && php artisan config:clear && php artisan route:cache && php artisan config:cache && echo '✅ Деплой завершен!' && php artisan route:list --path=trendagent/apartments/flat"
```

## Вариант 2: Использовать скрипт deploy.sh

```bash
bash deploy.sh
```

## Вариант 3: Выполнить команды пошагово

```bash
# 1. Подключиться к серверу
ssh root@89.169.39.244

# 2. Выполнить команды на сервере:
cd /var/www/AL
git stash || true
rm -f public/trendagent/assets/index-CkKWxhNq.css
rm -f public/trendagent/assets/index-CzMGkjVf.js
git fetch origin
git reset --hard origin/main
php artisan route:clear
php artisan config:clear
php artisan route:cache
php artisan config:cache
php artisan route:list --path=trendagent/apartments/flat
```

## Что делает скрипт:

1. ✅ Сохраняет локальные изменения (stash)
2. ✅ Удаляет конфликтующие файлы
3. ✅ Получает последние изменения из GitHub
4. ✅ Очищает кэш Laravel
5. ✅ Пересобирает кэш для производительности
6. ✅ Проверяет, что роут зарегистрирован

## После деплоя проверьте:

```bash
# На сервере:
php artisan route:list --path=trendagent/apartments/flat

# Должен быть виден:
# POST api/trendagent/apartments/{id}/flat/{apartmentId} ... TrendAgent\ApartmentsController@flatDetail
```
