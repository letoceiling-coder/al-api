# Инструкция по развертыванию TrendAgent Frontend

## Локальная разработка

1. Установите зависимости:
```bash
cd trendagent
npm install
```

2. Запустите dev сервер:
```bash
npm run dev
```

Приложение будет доступно по адресу `http://localhost:3001`

## Сборка для production

```bash
npm run build
```

Собранные файлы будут в `public/trendagent/`

## Настройка Nginx на сервере

### 1. Добавить конфигурацию в Nginx

Добавьте в конфигурацию `api.siteaccess.ru`:

```nginx
# Блок для обслуживания React приложения по /trendagent
location /trendagent {
    alias /var/www/al-api/public/trendagent;
    try_files $uri $uri/ /trendagent/index.html;
    
    # Кэширование статических файлов
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Отключить кэширование для index.html
    location = /trendagent/index.html {
        add_header Cache-Control "no-cache, no-store, must-revalidate";
        add_header Pragma "no-cache";
        add_header Expires "0";
    }
}
```

### 2. Проверить права доступа

```bash
chown -R www-data:www-data /var/www/al-api/public/trendagent
chmod -R 755 /var/www/al-api/public/trendagent
```

### 3. Перезагрузить Nginx

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Деплой на сервер

### Автоматический деплой через Git

1. Соберите проект локально:
```bash
npm run build
```

2. Закоммитьте и запушьте изменения:
```bash
git add trendagent/ public/trendagent/
git commit -m "Add TrendAgent React frontend"
git push origin main
```

3. На сервере выполните:
```bash
cd /var/www/al-api
git pull origin main
```

### Ручной деплой

1. Соберите проект:
```bash
npm run build
```

2. Скопируйте папку `public/trendagent` на сервер:
```bash
scp -r public/trendagent user@89.169.39.244:/var/www/al-api/public/
```

## Проверка работы

После настройки приложение должно быть доступно по адресу:
- `https://api.siteaccess.ru/trendagent`

## Структура API

Приложение использует следующие эндпоинты:

- `POST /api/trendagent/authenticate` - авторизация
- `GET /api/trendagent/cities` - список городов
- `POST /api/trendagent/apartments` - список квартир
- `POST /api/trendagent/apartments/{id}` - детали квартиры
- `POST /api/trendagent/parkings` - список паркингов
- `POST /api/trendagent/houses` - список домов
- `POST /api/trendagent/plots` - список участков
- `POST /api/trendagent/commercial` - коммерческая недвижимость
- `POST /api/trendagent/objects/list` - универсальный список

Все запросы требуют токен авторизации: `8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF`

## Устранение неполадок

### Приложение не загружается

1. Проверьте, что файлы скопированы в `public/trendagent/`
2. Проверьте права доступа к файлам
3. Проверьте конфигурацию Nginx
4. Проверьте логи Nginx: `sudo tail -f /var/log/nginx/error.log`

### API запросы не работают

1. Проверьте, что токен указан правильно в `src/services/api.js`
2. Проверьте, что API доступно по адресу `https://api.siteaccess.ru/api/trendagent`
3. Проверьте CORS настройки на сервере

### Ошибки сборки

1. Убедитесь, что установлены все зависимости: `npm install`
2. Проверьте версию Node.js (рекомендуется 18+)
3. Очистите кэш: `rm -rf node_modules package-lock.json && npm install`
