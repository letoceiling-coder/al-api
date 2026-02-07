# TrendAgent API - Документация

## 📚 Обзор

TrendAgent API предоставляет доступ к данным о недвижимости с сайта trendagent.ru. API использует Bearer token для аутентификации и поддерживает различные типы объектов недвижимости.

## 🔗 Доступ к документации

### Swagger UI (Интерактивная документация)
- **URL:** https://api.siteaccess.ru/trendagent/swagger
- **Описание:** Интерактивный интерфейс для тестирования API endpoints

### OpenAPI JSON
- **URL:** https://api.siteaccess.ru/trendagent/swagger.json
- **Описание:** Машиночитаемая спецификация API в формате OpenAPI 3.0

## 🔐 Аутентификация

Все запросы к API требуют Bearer token в заголовке `Authorization`:

```
Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF
```

**Важно:** Для выполнения запросов также требуется авторизация через TrendAgent SSO с использованием `phone` и `password` в теле запроса.

## 📋 Основные эндпоинты

### 1. Аутентификация

#### POST `/trendagent/authenticate`
Авторизация через TrendAgent SSO API.

**Запрос:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q"
}
```

**Ответ:**
```json
{
  "success": true,
  "message": "Авторизация успешна",
  "data": {
    "authenticated": true,
    "auth_token": "...",
    "tokens": [],
    "user": {}
  }
}
```

### 2. Список городов

#### GET `/trendagent/cities`
Получение списка доступных городов.

**Параметры запроса:**
- `phone` (required) - номер телефона
- `password` (required) - пароль

### 3. Квартиры

#### POST `/trendagent/apartments`
Получение списка квартир с фильтрами.

**Параметры запроса:**
- `phone` (required) - номер телефона
- `password` (required) - пароль
- `city` (optional) - город для фильтрации
- `count` (optional, default: 20) - количество объектов на странице (1-100)
- `offset` (optional, default: 0) - смещение для пагинации
- `page` (optional) - номер страницы (альтернатива offset)
- `sort` (optional) - поле сортировки: `price`, `deadline`, `name`
- `sort_order` (optional) - порядок сортировки: `asc`, `desc`
- `room` (optional) - массив количества комнат: `[1, 2, 3]`
- `price_from` (optional) - минимальная цена
- `price_to` (optional) - максимальная цена
- `area_from` (optional) - минимальная площадь
- `area_to` (optional) - максимальная площадь
- `floor_from` (optional) - минимальный этаж
- `floor_to` (optional) - максимальный этаж
- `finishing_types` (optional) - массив типов отделки
- `deadline_key` (optional) - ключ срока сдачи
- `text` (optional) - текстовый поиск
- `subway` (optional) - фильтр по метро
- `region` (optional) - фильтр по региону
- `district` (optional) - фильтр по району

**Пример запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "city": "spb",
  "count": 20,
  "page": 1,
  "sort": "price",
  "sort_order": "asc",
  "room": [1, 2],
  "price_from": 2000000,
  "price_to": 10000000
}
```

#### POST `/trendagent/apartments/{id}`
Получение детальной информации о блоке/ЖК.

**Параметры пути:**
- `id` - ID или GUID блока (например: `63c50acc9a85d53360f63a76` или `dom-na-naberezhnoy-st`)

**Параметры запроса:**
- `phone` (required) - номер телефона
- `password` (required) - пароль
- `options` (optional) - объект с опциями:
  - `unified` (default: true) - унифицированные данные
  - `buildings` (default: true) - корпуса
  - `apartments` (default: true) - квартиры
  - `plans` (default: true) - планы
  - `progress` (default: true) - ход строительства
  - `finishings` (default: true) - отделка
  - `advantages` (default: true) - преимущества
  - `nearby_places` (default: true) - ближайшие места
  - `min_price` (default: true) - минимальная цена
  - `videos` (default: true) - видео
  - `files` (default: true) - файлы
  - `apartments_params` (optional) - параметры фильтрации квартир

#### POST `/trendagent/apartments/{id}/flat/{apartmentId}`
Получение детальной информации о квартире.

**Параметры пути:**
- `id` - ID блока/ЖК
- `apartmentId` - ID квартиры

**Параметры запроса:**
- `phone` (required) - номер телефона
- `password` (required) - пароль
- `block` (optional, query) - ID блока
- `guid` (optional, query) - GUID объекта

#### POST `/trendagent/apartments/{id}/checkerboard/buildings`
Получение корпусов для шахматки.

**Параметры пути:**
- `id` - ID блока (24 символа hex)

**Параметры запроса:**
- `phone` (required) - номер телефона
- `password` (required) - пароль
- `room` (optional) - массив количества комнат
- `guid` (optional, query) - GUID объекта
- `apartments-onrequest` (optional, query) - показывать только квартиры под запрос

#### POST `/trendagent/apartments/{id}/checkerboard/apartments`
Получение квартир для шахматки.

**Параметры пути:**
- `id` - ID блока (24 символа hex)

**Параметры запроса:**
- `phone` (required) - номер телефона
- `password` (required) - пароль
- `building_id` (required) - ID корпуса
- `guid` (optional, query) - GUID объекта

#### POST `/trendagent/apartments/{id}/floor-plan/directory`
Получение справочника поэтажного плана.

**Параметры пути:**
- `id` - ID блока/ЖК

**Параметры запроса:**
- `phone` (required) - номер телефона
- `password` (required) - пароль

#### POST `/trendagent/apartments/{id}/floor-plan`
Получение поэтажного плана.

**Параметры пути:**
- `id` - ID блока/ЖК

**Параметры запроса:**
- `phone` (required) - номер телефона
- `password` (required) - пароль
- `building_id` (required) - ID корпуса
- `section_id` (required) - ID секции
- `floor_number` (required) - номер этажа

### 4. Дома

#### POST `/trendagent/houses`
Получение списка домов (коттеджи, таунхаусы).

#### POST `/trendagent/houses/{id}`
Получение детальной информации о доме.

#### POST `/trendagent/houses/{id}/checkerboard/buildings`
Получение корпусов для шахматки домов.

#### POST `/trendagent/houses/{id}/checkerboard/apartments`
Получение квартир для шахматки домов.

### 5. Участки

#### POST `/trendagent/plots`
Получение списка участков (поселки).

#### POST `/trendagent/plots/{id}`
Получение детальной информации о поселке.

#### POST `/trendagent/plots/{id}/plot/{plotId}`
Получение детальной информации об участке.

### 6. Паркинги

#### POST `/trendagent/parkings`
Получение списка паркингов.

#### POST `/trendagent/parkings/{id}`
Получение детальной информации о паркинге.

#### POST `/trendagent/parkings/{id}/places`
Получение мест парковки.

### 7. Коммерческая недвижимость

#### POST `/trendagent/commercial`
Получение списка коммерческой недвижимости.

#### POST `/trendagent/commercial/{id}`
Получение детальной информации о коммерческой недвижимости.

### 8. Проекты домов

#### POST `/trendagent/houseprojects`
Получение списка проектов домов.

#### POST `/trendagent/houseprojects/{id}`
Получение детальной информации о проекте дома.

### 9. Универсальные операции

#### POST `/trendagent/objects/list`
Универсальный список объектов различных типов.

**Параметры запроса:**
- `phone` (required) - номер телефона
- `password` (required) - пароль
- `object_type` (optional) - тип объекта: `apartments`, `parking`, `houses`, `plots`, `commercial`, `contractors`
- `count` (optional, default: 20) - количество объектов
- `offset` (optional, default: 0) - смещение
- `page` (optional) - номер страницы
- `sort` (optional) - поле сортировки
- `sort_order` (optional) - порядок сортировки: `asc`, `desc`
- `room` (optional) - массив количества комнат
- `city` (optional) - город
- `price_from` (optional) - минимальная цена
- `price_to` (optional) - максимальная цена

#### POST `/trendagent/block/details`
Получение детальной информации о блоке.

#### POST `/trendagent/block/{dataType}`
Получение данных блока по типу.

## 📝 Формат ответов

### Успешный ответ
```json
{
  "success": true,
  "data": { ... },
  "message": "Операция выполнена успешно"
}
```

### Ответ с ошибкой
```json
{
  "success": false,
  "message": "Описание ошибки",
  "errors": {
    "field": ["Сообщение об ошибке"]
  }
}
```

## 🔢 Коды ответов

- `200` - Успешный запрос
- `422` - Ошибка валидации
- `500` - Ошибка сервера

## 📖 Примеры использования

### cURL

```bash
# Авторизация
curl -X POST https://api.siteaccess.ru/trendagent/authenticate \
  -H "Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+79045393434",
    "password": "nwBvh4q"
  }'

# Список квартир
curl -X POST https://api.siteaccess.ru/trendagent/apartments \
  -H "Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+79045393434",
    "password": "nwBvh4q",
    "city": "spb",
    "count": 20,
    "room": [1, 2],
    "price_from": 2000000,
    "price_to": 10000000
  }'
```

### JavaScript (fetch)

```javascript
// Авторизация
const response = await fetch('https://api.siteaccess.ru/trendagent/authenticate', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    phone: '+79045393434',
    password: 'nwBvh4q'
  })
});

const data = await response.json();
console.log(data);
```

## 🛠️ Интеграция

### Swagger UI
Для интерактивного тестирования API используйте Swagger UI:
https://api.siteaccess.ru/trendagent/swagger

### OpenAPI спецификация
Для автоматической генерации клиентов используйте OpenAPI JSON:
https://api.siteaccess.ru/trendagent/swagger.json

## 📞 Поддержка

По вопросам использования API обращайтесь:
- Email: support@siteaccess.ru
- Документация: https://api.siteaccess.ru/trendagent/swagger

## 📄 Версия API

Текущая версия: **1.0.0**

---

**Примечание:** Все запросы требуют Bearer token в заголовке `Authorization` и авторизацию через TrendAgent SSO с использованием `phone` и `password` в теле запроса.
