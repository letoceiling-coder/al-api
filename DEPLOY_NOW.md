# 🚀 Быстрый деплой - выполнить сейчас

## Команды для выполнения на сервере:

```bash
ssh root@89.169.39.244
cd /var/www/AL
git pull origin main
php artisan route:clear
php artisan config:clear
php artisan config:cache
echo "✅ Деплой завершен!"
```

## Или одной командой:

```bash
ssh root@89.169.39.244 "cd /var/www/AL && git pull origin main && php artisan route:clear && php artisan config:clear && php artisan config:cache && echo '✅ Деплой завершен!'"
```

## Что будет обновлено:

1. ✅ Кэширование для `getApartmentDetail()` - 60 минут
2. ✅ Улучшенная обработка ошибок - извлечение текста из HTML
3. ✅ Расширенное логирование - полный URL, статус, тело ответа

## После деплоя проверьте:

```bash
# Проверить логи
tail -n 50 storage/logs/laravel.log | grep "apartment detail"
```
