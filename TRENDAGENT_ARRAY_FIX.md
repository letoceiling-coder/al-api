# Исправление ошибок с массивами и маршрутизацией

**Дата:** 2026-02-06  
**Проблемы:**
1. `TypeError: a.map is not a function` - попытка вызвать `.map()` на не-массиве
2. Прямая ссылка ведет на `/frontend` вместо `/trendagent`

## Проблема 1: Ошибка `.map() is not a function`

### Причина:
API возвращает данные в различных форматах:
- Иногда как массив: `{ data: [...] }`
- Иногда как объект: `{ data: {...} }`
- Иногда как массив напрямую: `[...]`
- Иногда как `null` или `undefined`

Компоненты пытались вызвать `.map()` на данных, которые не являются массивами.

### Решение:

Добавлены проверки `Array.isArray()` во всех компонентах:

**ObjectApartments.jsx:**
```javascript
// Было:
const apartments = apartmentsData?.data || apartmentsData || []

// Стало:
const apartments = Array.isArray(apartmentsData?.data) 
  ? apartmentsData.data 
  : (Array.isArray(apartmentsData) ? apartmentsData : [])
```

**ObjectParkings.jsx:**
```javascript
const parkings = Array.isArray(parkingsData?.data) 
  ? parkingsData.data 
  : (Array.isArray(parkingsData) ? parkingsData : [])
```

**ObjectCommerce.jsx:**
```javascript
const premises = Array.isArray(commerceData?.data) 
  ? commerceData.data 
  : (Array.isArray(commerceData) ? commerceData : [])
```

**ObjectProgress.jsx:**
```javascript
const progress = Array.isArray(progressData?.data) 
  ? progressData.data 
  : (Array.isArray(progressData) ? progressData : [])
```

**ObjectFinishing.jsx:**
```javascript
const finishingsList = Array.isArray(finishings?.data) 
  ? finishings.data 
  : (Array.isArray(finishings) ? finishings : [])
```

**ObjectAdvantages.jsx:**
```javascript
const advantagesList = Array.isArray(advantages?.data) 
  ? advantages.data 
  : (Array.isArray(advantages) ? advantages : [])
```

**ObjectLocation.jsx:**
```javascript
const places = Array.isArray(nearbyPlaces?.data) 
  ? nearbyPlaces.data 
  : (Array.isArray(nearbyPlaces) ? nearbyPlaces : [])
```

**ObjectVideos.jsx:**
```javascript
const videosList = Array.isArray(videos?.data) 
  ? videos.data 
  : (Array.isArray(videos) ? videos : [])
```

**ObjectFiles.jsx:**
```javascript
const filesList = Array.isArray(files?.data) 
  ? files.data 
  : (Array.isArray(files) ? files : [])
```

**ObjectDetail.jsx:**
```javascript
// Безопасное извлечение данных с проверкой типов
const buildingsData = Array.isArray(objectData.buildings?.data) 
  ? objectData.buildings.data 
  : (Array.isArray(objectData.buildings) ? objectData.buildings : [])
const plansData = Array.isArray(objectData.plans?.data) 
  ? objectData.plans.data 
  : (Array.isArray(objectData.plans) ? objectData.plans : [])
```

## Проблема 2: Маршрутизация Nginx

### Причина:
Блок `location /trendagent` не был добавлен в конфигурацию Nginx, поэтому запросы обрабатывались блоком `location /`, который перенаправлял на `/frontend`.

### Решение:

Добавлен блок в конфигурацию Nginx `/etc/nginx/sites-available/api.siteaccess.ru`:

```nginx
# TrendAgent React app
location /trendagent {
    alias /var/www/AL/public/trendagent;
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

**Важно:** Блок должен быть размещен ПЕРЕД блоком `location /`, чтобы иметь приоритет.

## Результат

✅ Все компоненты проверяют, что данные являются массивами перед использованием `.map()`  
✅ Nginx правильно обрабатывает запросы к `/trendagent`  
✅ Прямые ссылки работают корректно  
✅ Ошибки `.map() is not a function` исправлены

## Проверка

После обновления:
1. ✅ Прямые ссылки на объекты работают: `https://api.siteaccess.ru/trendagent/apartments/{id}?guid={guid}`
2. ✅ Все секции детальной информации отображаются без ошибок
3. ✅ Нет ошибок в консоли браузера

**URL для проверки:** https://api.siteaccess.ru/trendagent/apartments/63c50acc9a85d53360f63a76?guid=dom-na-naberezhnoy-st
