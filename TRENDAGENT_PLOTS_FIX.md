# Исправление отображения участков

**Дата:** 2026-02-06  
**Проблема:** Участки отображались без данных (название "Без названия", цена "Цена не указана", нет фотографий)

## Проблема

При отображении участков в списке объектов:
- Все карточки показывали "Без названия" вместо реального названия
- Все карточки показывали "Цена не указана" вместо реальной цены
- Все карточки показывали placeholder вместо фотографий

## Причина

Структура данных для участков отличается от других типов объектов:

1. **Цена:** Для участков цена хранится в массиве `min_prices` (массив объектов с `label`, `value`, `unit`), а не в простых полях `min_price` или `price`.

2. **Изображения:** Для участков изображения приходят в структуре:
   ```javascript
   images: [
     {
       thumbnail: "https://selcdn.trendagent.ru/images/.../m_...",
       full: "https://selcdn.trendagent.ru/images/.../...",
       path: "...",
       file_name: "..."
     }
   ]
   ```

3. **Название:** Может быть в поле `name` или `village_name`.

## Решение

### 1. Обновлен метод `getName()` в ObjectCard.jsx

```javascript
const getName = () => {
  // Для участков может быть название в разных полях
  if (objectType === 'plots') {
    return object.name || object.village_name || 'Без названия'
  }
  return object.name || 'Без названия'
}
```

### 2. Обновлен метод `getPrice()` в ObjectCard.jsx

```javascript
const getPrice = () => {
  // Для участков цена хранится в массиве min_prices
  if (objectType === 'plots' && object.min_prices && Array.isArray(object.min_prices) && object.min_prices.length > 0) {
    // Берем первую цену из массива
    const firstPrice = object.min_prices[0]
    if (firstPrice.value) {
      const unit = firstPrice.unit || '₽'
      const label = firstPrice.label ? `${firstPrice.label}: ` : ''
      return `${label}${formatPrice(firstPrice.value)} ${unit}`
    }
  }
  
  // Стандартная обработка для других типов
  // ...
}
```

### 3. Обновлен метод `getImage()` в ObjectCard.jsx

```javascript
const getImage = () => {
  // Сначала проверяем массив images (для паркингов, домов, участков, коммерции)
  if (object.images && Array.isArray(object.images) && object.images.length > 0) {
    const firstImage = object.images[0]
    
    // Для участков images может быть массивом объектов с thumbnail/full
    if (firstImage.thumbnail) {
      return firstImage.thumbnail
    }
    if (firstImage.full) {
      return firstImage.full
    }
    
    // Если есть path и file_name, формируем URL
    if (firstImage.path && firstImage.file_name) {
      const path = firstImage.path.replace(/^\/+|\/+$/g, '')
      const fileName = firstImage.file_name
      return `https://selcdn.trendagent.ru/images/${path}/m_${fileName}`
    }
    
    // Пробуем через imageUtils
    return getImageUrl(firstImage)
  }
  
  // ...
}
```

## Структура данных участков из API

Данные участков приходят в следующей структуре:

```javascript
{
  id: "...",
  guid: "...",
  name: "Название поселка",
  address: "Адрес",
  plots_count: 10,
  view_plots_count: 5,
  builder: {...},
  distance: {
    center: "...",
    railway: "...",
    highway: "..."
  },
  deadline: {
    value: "..."
  },
  min_prices: [
    {
      label: "от",
      value: 1000000,
      unit: "₽"
    }
  ],
  reward: "...",
  reward_hint: "...",
  sales_start: "...",
  images: [
    {
      thumbnail: "https://selcdn.trendagent.ru/images/.../m_...",
      full: "https://selcdn.trendagent.ru/images/.../...",
      path: "...",
      file_name: "..."
    }
  ],
  is_new_village: false,
  property_types: [...]
}
```

## Результат

✅ **Названия участков отображаются корректно**  
✅ **Цены участков отображаются из массива min_prices**  
✅ **Фотографии участков отображаются из массива images**  
✅ **Обработка данных для участков выделена в отдельные условия**

## Проверка

После обновления:
1. ✅ Названия участков отображаются
2. ✅ Цены участков отображаются с правильным форматом
3. ✅ Фотографии участков отображаются
4. ✅ Нет ошибок в консоли браузера

**URL для проверки:** https://api.siteaccess.ru/trendagent (выберите тип "Участки")
