# 🔧 Настройка Redis на сервере

## Проблема

Ошибка: `Class "Predis\Client" not found` - Redis/Predis не настроен на сервере.

## ✅ Решение (2 варианта)

### Вариант 1: Использовать File Cache (проще, рекомендуется)

**Выполните на сервере:**

```bash
cd /var/www/AL

# Запустить скрипт настройки File Cache
bash setup_cache_file.sh

# Или вручную:
# 1. Отредактировать .env
nano .env

# 2. Изменить/добавить:
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# 3. Очистить кеш
php artisan config:clear
php artisan cache:clear
```

**Преимущества:**
- ✅ Не требует установки Redis
- ✅ Работает сразу
- ✅ Проще в настройке

---

### Вариант 2: Установить Redis и Predis (если нужен Redis)

**Выполните на сервере:**

```bash
cd /var/www/AL

# Запустить скрипт настройки Redis
bash setup_redis_server.sh

# Или вручную:
# 1. Установить Redis
apt-get update
apt-get install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# 2. Установить Predis через Composer
composer require predis/predis

# 3. Проверить .env
# CACHE_DRIVER=redis
# REDIS_HOST=127.0.0.1
# REDIS_PORT=6379

# 4. Очистить кеш
php artisan config:clear
php artisan cache:clear
```

---

## 🔍 Проверка

После настройки проверьте:

```bash
# Проверить конфигурацию
php artisan config:show cache

# Проверить работу кеша
php artisan cache:clear
php artisan config:cache
```

---

## 📝 Рекомендация

**Используйте Вариант 1 (File Cache)** - это проще и не требует дополнительных сервисов. Redis нужен только если:
- Высокая нагрузка на кеш
- Нужны очереди через Redis
- Нужны сессии через Redis

Для большинства случаев File Cache достаточно.

---

## 🚀 После настройки

Парсер должен работать без ошибок:

```bash
php artisan trendagent:parse --region=spb --type=complexes
```
