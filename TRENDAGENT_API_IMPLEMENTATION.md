# TrendAgent API Implementation

## Обзор

Реализован полнофункциональный API для парсинга `trendagent.ru` в рамках существующего Laravel проекта `AL`. API полностью изолирован от основного проекта и использует отдельный токен для аутентификации.

## Аутентификация

Все эндпоинты TrendAgent API требуют специальный токен в заголовке `Authorization`:

```
Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF
```

**Важно:** Этот токен отличается от токена основного API Gateway (Sanctum).

## Структура проекта

### Middleware
- `app/Http/Middleware/TrendAgentAuthMiddleware.php` - Middleware для проверки токена TrendAgent API

### Routes
- `routes/trendagent.php` - Все маршруты TrendAgent API

### Controllers
- `app/Http/Controllers/TrendAgent/ApartmentsController.php` - Квартиры
- `app/Http/Controllers/TrendAgent/ParkingsController.php` - Паркинги
- `app/Http/Controllers/TrendAgent/HousesController.php` - Дома с участками
- `app/Http/Controllers/TrendAgent/PlotsController.php` - Участки
- `app/Http/Controllers/TrendAgent/CommercialController.php` - Коммерческая недвижимость
- `app/Http/Controllers/TrendAgent/TrendSsoController.php` - SSO авторизация и общие методы

### Services
- `app/Services/TrendAgent/TrendSsoApiAuth.php` - Сервис для авторизации и работы с Trend SSO API
- `app/Services/TrendAgent/CityService.php` - Сервис для работы с городами

## Поддерживаемые регионы

API поддерживает следующие регионы:

1. **Москва** (msk) - ID: `5a5cb42159042faa9a218d04`
2. **Санкт-Петербург** (spb) - ID: `58c665588b6aa52311afa01b`
3. **Краснодарский край, Сочи, Республика Адыгея** (krd) - ID: `604b5243f9760700074ac345`
4. **Ростов-на-Дону** (rnd) - ID: `61926fb5bb267a0008de132b`
5. **Крым** (crimea) - ID: `682700dd0e7daf77097d0779`
6. **Казань** (kzn) - ID: `642157fca50429d21e3aa14f`
7. **Уфа** (ufa) - ID: `674eff862307c824cf56ced3`
8. **Екатеринбург** (ekb) - ID: `650974f78d34c0f790a012a9`
9. **Новосибирск** (nsk) - ID: `618120c1a56997000866c4d8`
10. **UAE** (dubai) - ID: `63d10e79a8975354f0d41c80`

## API Endpoints

### Base URL
```
https://api.siteaccess.ru/api/trendagent
```

### 1. SSO Authentication & Cities

#### POST `/api/trendagent/authenticate`
Авторизация через Trend SSO.

**Request:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Авторизация успешна",
  "data": {
    "authenticated": true,
    "auth_token": "...",
    "tokens": {...},
    "cookies": {...}
  }
}
```

#### GET `/api/trendagent/cities`
Получение списка всех доступных городов.

### 2. Apartments (Квартиры)

#### POST `/api/trendagent/apartments`
Получение списка квартир с фильтрами.

**Request:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "city": "58c665588b6aa52311afa01b",
  "count": 20,
  "offset": 0,
  "sort": "price",
  "sort_order": "asc",
  "room": [1, 2, 3],
  "price_from": 3000000,
  "price_to": 10000000,
  "area_from": 30,
  "area_to": 100
}
```

#### POST `/api/trendagent/apartments/{id}`
Получение детальной информации об объекте (блоке/ЖК).

**Request:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "options": {
    "unified": true,
    "apartments": true,
    "plans": true,
    "progress": true,
    "finishings": true,
    "advantages": true,
    "nearby_places": true,
    "min_price": true,
    "videos": true,
    "files": true,
    "apartments_params": {
      "city": "58c665588b6aa52311afa01b",
      "lang": "ru"
    }
  }
}
```

### 3. Parkings (Паркинги)

#### POST `/api/trendagent/parkings`
Получение списка паркингов.

#### POST `/api/trendagent/parkings/{id}`
Получение детальной информации о паркинге.

#### POST `/api/trendagent/parkings/{id}/places`
Получение мест парковки для блока.

### 4. Houses (Дома с участками)

#### POST `/api/trendagent/houses`
Получение списка домов с участками.

#### POST `/api/trendagent/houses/{id}`
Получение детальной информации о доме.

### 5. Plots (Участки)

#### POST `/api/trendagent/plots`
Получение списка участков.

#### POST `/api/trendagent/plots/{id}`
Получение детальной информации об участке.

### 6. Commercial (Коммерческая недвижимость)

#### POST `/api/trendagent/commercial`
Получение списка коммерческой недвижимости.

#### POST `/api/trendagent/commercial/{id}`
Получение детальной информации о коммерческой недвижимости.

### 7. Objects List (Универсальный список)

#### POST `/api/trendagent/objects/list`
Универсальный метод для получения списка объектов любого типа.

**Request:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "object_type": "apartments",
  "city": "58c665588b6aa52311afa01b",
  "count": 20,
  "page": 1
}
```

**Поддерживаемые типы объектов:**
- `apartments` - Квартиры
- `parking` - Паркинги
- `houses` - Дома с участками
- `plots` - Участки
- `commercial` - Коммерческая недвижимость
- `contractors` - Подрядчики

### 8. Block Details (Детали блока)

#### POST `/api/trendagent/block/details`
Получение детальной информации о блоке.

#### POST `/api/trendagent/block/{dataType}`
Получение конкретного типа данных блока (unified, buildings, apartments, plans, progress, и т.д.).

## Фильтры

### Общие фильтры (для всех типов объектов):
- `city` - ID города
- `count` - Количество объектов на странице (1-100)
- `offset` - Смещение для пагинации
- `page` - Номер страницы (альтернатива offset)
- `sort` - Поле сортировки: `price`, `deadline`, `name`
- `sort_order` - Направление сортировки: `asc`, `desc`
- `price_from` - Минимальная цена (₽)
- `price_to` - Максимальная цена (₽)
- `area_from` - Минимальная площадь (м²)
- `area_to` - Максимальная площадь (м²)
- `text` - Поиск по тексту (название, адрес)
- `subway` - ID станции метро
- `region` - ID региона
- `district` - ID района

### Специфичные фильтры:

#### Для квартир (Apartments):
- `room` - Массив типов квартир (1, 2, 3, 4, ...)
- `floor_from` - Минимальный этаж
- `floor_to` - Максимальный этаж
- `finishing_types` - Типы отделки (0, 1, 2, ...)
- `deadline_key` - Ключ срока сдачи (например: "2024", "2025")

#### Для паркингов (Parkings):
- `parking_type` - Тип парковки

#### Для коммерческой недвижимости (Commercial):
- `purpose` - Назначение помещения

## Формат ответов

### Успешный ответ:
```json
{
  "success": true,
  "data": {...},
  "pagination": {
    "count": 20,
    "offset": 0,
    "page": 1,
    "has_more": true,
    "returned_count": 20
  }
}
```

### Ошибка:
```json
{
  "success": false,
  "message": "Описание ошибки",
  "errors": {
    "field_name": ["Сообщение об ошибке валидации"]
  }
}
```

## Коды ошибок

- `401` - Неавторизован (неверный или отсутствующий токен TrendAgent API)
- `422` - Ошибка валидации данных
- `500` - Внутренняя ошибка сервера

## Изоляция от основного проекта

TrendAgent API полностью изолирован от основного проекта:

1. **Отдельный middleware** для аутентификации (`TrendAgentAuthMiddleware`)
2. **Отдельный файл маршрутов** (`routes/trendagent.php`)
3. **Отдельные контроллеры** в `app/Http/Controllers/TrendAgent/`
4. **Отдельные сервисы** в `app/Services/TrendAgent/`
5. **Отдельный токен** для аутентификации

## Зависимости

API использует следующие зависимости (уже установлены в проекте):
- `guzzlehttp/guzzle` - HTTP клиент для работы с Trend SSO API
- `illuminate/support` - Laravel компоненты

## Примеры использования

### JavaScript (Axios):
```javascript
const token = '8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF';

// Получение списка квартир
const response = await axios.post('https://api.siteaccess.ru/api/trendagent/apartments', {
  phone: '+79045393434',
  password: 'nwBvh4q',
  city: '58c665588b6aa52311afa01b',
  count: 20,
  sort: 'price',
  sort_order: 'asc',
  room: [1, 2, 3],
  price_from: 3000000,
  price_to: 10000000
}, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

### cURL:
```bash
curl -X POST "https://api.siteaccess.ru/api/trendagent/apartments" \
  -H "Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+79045393434",
    "password": "nwBvh4q",
    "city": "58c665588b6aa52311afa01b",
    "count": 20
  }'
```

## Примечания

1. **Авторизация в Trend SSO:** Все запросы (кроме `/authenticate` и `/cities`) требуют передачи `phone` и `password` в теле запроса для авторизации в Trend SSO.

2. **Идентификаторы объектов:** Можно использовать как MongoDB ObjectId (24 символа), так и GUID объекта (строка, например: `villa-marina`).

3. **Пагинация:** Используйте параметры `count` и `offset` (или `page`) для навигации по страницам. Поле `has_more` в ответе указывает, есть ли еще данные.

4. **Таймауты:** Рекомендуется устанавливать таймаут не менее 120 секунд (2 минуты) для запросов детальной информации, так как они могут загружать большой объем данных.

## Версия API

**Текущая версия:** 1.0  
**Дата реализации:** 2026-02-06

## Дальнейшее развитие

Планируется:
- [ ] Добавление OpenAPI/Swagger документации
- [ ] Создание моделей для хранения данных в БД
- [ ] Реализация кэширования часто запрашиваемых данных
- [ ] Добавление rate limiting для защиты от злоупотреблений
- [ ] Расширенное логирование запросов
