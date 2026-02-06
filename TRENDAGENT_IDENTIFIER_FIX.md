# Исправление проблемы с идентификаторами объектов

**Дата:** 2026-02-06  
**Проблема:** Ошибка "Блок с GUID не найден" при переходе на детальную страницу объекта

## Проблема

При переходе на детальную страницу объекта возникала ошибка:
```
Блок с GUID trend-144-9 не найден
POST https://api.siteaccess.ru/api/trendagent/apartments/trend-144-9 500 (Internal Server Error)
```

### Причины:

1. **Неправильная логика выбора идентификатора:**
   - В `ObjectCard.jsx` метод `getObjectId()` возвращал `object._id || object.id || object.guid`
   - Это приводило к тому, что если не было `_id` или `id`, использовался `guid` как основной идентификатор
   - В URL попадал `guid` в параметрах маршрута, а затем он же дублировался в query параметрах

2. **Неправильный приоритет в ObjectDetail:**
   - Использовалась логика `guid || id`, что давало приоритет `guid`
   - Но API лучше работает с MongoDB ObjectId (24 символа hex), если он есть

3. **Отсутствие валидации:**
   - Не проверялось, является ли идентификатор валидным MongoDB ObjectId

## Решение

### 1. Исправлена логика в ObjectCard.jsx

**Было:**
```javascript
const getObjectId = () => {
  return object._id || object.id || object.guid  // ❌ guid попадал в id
}

const linkTo = `/${objectType}/${objectId}${objectGuid ? `?guid=${objectGuid}` : ''}`
```

**Стало:**
```javascript
const getObjectId = () => {
  // Приоритет: _id, затем id, но не guid (guid используется отдельно)
  return object._id || object.id || null  // ✅ guid не используется как id
}

// Формируем URL: если есть ID, используем его, иначе используем GUID
const identifier = objectId || objectGuid
const linkTo = identifier ? `/${objectType}/${identifier}${objectId && objectGuid ? `?guid=${objectGuid}` : ''}` : '#'
```

### 2. Исправлена логика в ObjectDetail.jsx

**Было:**
```javascript
const objectId = guid || id  // ❌ Приоритет guid
```

**Стало:**
```javascript
// Определяем, какой идентификатор использовать
// Приоритет: если id является валидным MongoDB ObjectId (24 символа hex), используем id
// Иначе используем guid, если он есть
const isValidMongoId = id && /^[a-f0-9]{24}$/i.test(id)
const objectId = isValidMongoId ? id : (guid || id)  // ✅ Приоритет валидного ID
```

### 3. Добавлена обработка ошибок

- Добавлено логирование для отладки
- Улучшена обработка ошибок API
- Более информативные сообщения об ошибках

## Логика работы API

API `getBlockFullData` определяет тип идентификатора:
- Если идентификатор соответствует формату MongoDB ObjectId (24 символа hex) → используется как ID
- Иначе → используется как GUID, и выполняется поиск ID по GUID

## Результат

✅ Правильное формирование URL в карточках объектов  
✅ Корректный выбор идентификатора при загрузке детальной информации  
✅ Валидация MongoDB ObjectId  
✅ Улучшенная обработка ошибок  
✅ Детальное логирование для отладки

## Примеры работы

### Случай 1: Есть и ID и GUID
- Объект: `{ _id: "66c63f38ed717586fd8ebd64", guid: "vysota-spb" }`
- URL: `/apartments/66c63f38ed717586fd8ebd64?guid=vysota-spb`
- Используется: `66c63f38ed717586fd8ebd64` (валидный MongoDB ObjectId)

### Случай 2: Только GUID
- Объект: `{ guid: "vysota-spb" }`
- URL: `/apartments/vysota-spb`
- Используется: `vysota-spb` (GUID)

### Случай 3: Только ID
- Объект: `{ _id: "66c63f38ed717586fd8ebd64" }`
- URL: `/apartments/66c63f38ed717586fd8ebd64`
- Используется: `66c63f38ed717586fd8ebd64` (ID)

## Проверка

После обновления:
1. ✅ Карточки объектов формируют правильные URL
2. ✅ Детальная страница корректно определяет идентификатор
3. ✅ API успешно находит объекты по ID или GUID
4. ✅ Ошибки обрабатываются с информативными сообщениями

**URL для проверки:** https://api.siteaccess.ru/trendagent/apartments/66c63f38ed717586fd8ebd64?guid=vysota-spb
