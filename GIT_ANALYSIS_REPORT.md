# Отчет: Анализ изменений главной страницы TrendAgent

**Дата анализа:** 2026-02-13  
**Коммит для сравнения:** `c14af0f` (2026-02-13 05:20:06) - самый ранний коммит с изменениями  
**Текущее состояние:** последний коммит `ac85138` (2026-02-13 06:30:35)

---

## 📊 ЧТО БЫЛО РАНЬШЕ (коммит c14af0f)

### Главная страница (`ObjectsList.jsx`)

**Что выводилось:**
- **КВАРТИРЫ** (apartments)
- При `selectedObjectType === 'apartments'` вызывался `trendAgentAPI.getApartments(params)`
- Загружались данные из `response.data?.objects || response.data?.data || []`

**Код загрузки:**
```javascript
if (selectedObjectType === 'apartments') {
  response = await trendAgentAPI.getApartments(params)
}
```

**Зависимости useEffect:**
```javascript
useEffect(() => {
  if (authData && authData.authenticated) {
    loadObjects()
  }
}, [selectedObjectType, filters, pagination.offset, authData])
// НЕ было viewType в зависимостях!
```

---

### Отображение фото (`ObjectCard.jsx`)

**Метод `getImage()` проверял в порядке приоритета:**

1. **`object.images`** (массив) - для паркингов, домов, участков, коммерции
   - Проверял `firstImage.thumbnail`
   - Проверял `firstImage.full`
   - Проверял `firstImage.path` и `firstImage.file_name` → формировал URL
   - Пробовал через `getImageUrl(firstImage)`

2. **`object.image`** (одиночное поле) - для квартир и других типов
   - Пробовал через `getImageUrl(object.image)`

3. **`object.renderer`** (массив)
   - Пробовал через `getImageUrl(object.renderer[0])`

4. **Дополнительные поля:**
   - `object.photo` → через `getImageUrl`
   - `object.photo_url` → напрямую
   - `object.image_url` → напрямую
   - `object.gallery` → через `getImageUrl`

**Важно:** НЕ было проверки `object.image.url`, `object.image.thumbnail` напрямую!

---

## 🔄 ЧТО ИЗМЕНИЛОСЬ СЕЙЧАС

### Главная страница (`ObjectsList.jsx`)

**Что выводится сейчас:**
- **КОМПЛЕКСЫ (блоки)** при `viewType === 'list'`
- При `selectedObjectType === 'apartments' && viewType === 'list'` вызывается `trendAgentAPI.getObjectsList('', params)`
- Загружаются данные из `response.data?.objects` (для блоков)

**Код загрузки:**
```javascript
if (selectedObjectType === 'apartments' && viewType === 'list') {
  // Загружаем комплексы (блоки) вместо квартир
  const blocksParams = { ...params }
  delete blocksParams.object_type
  response = await trendAgentAPI.getObjectsList('', blocksParams)
} else if (selectedObjectType === 'apartments') {
  response = await trendAgentAPI.getApartments(params)
}
```

**Зависимости useEffect:**
```javascript
useEffect(() => {
  if (authData && authData.authenticated) {
    loadObjects()
  }
}, [selectedObjectType, viewType, filters, pagination.offset, authData])
// ДОБАВЛЕН viewType в зависимости!
```

---

### Отображение фото (`ObjectCard.jsx`)

**Метод `getImage()` теперь проверяет:**

1. **`object.images`** (массив) - без изменений

2. **`object.image`** (одиночное поле) - **ИЗМЕНЕНО:**
   - **Сначала проверяет готовые URL поля напрямую:**
     - `object.image.url` ✅
     - `object.image.thumbnail` ✅
     - `object.image.url_full` ✅
     - `object.image.full` ✅
   - Затем пробует через `getImageUrl(object.image)`

3. **`object.renderer`** - без изменений

4. **Дополнительные поля** - **ДОБАВЛЕНО:**
   - `object.media` (массив) ✅
   - `object.preview_image` ✅
   - `object.cover_image` ✅
   - `object.main_image` ✅

5. **Для квартир (`objectType === 'apartments'`):**
   - **ДОБАВЛЕНО:** Проверка `object.block.image` и `object.block.images` ✅
   - **ДОБАВЛЕНО:** Проверка `object.block_image` ✅

---

## 🎯 КЛЮЧЕВЫЕ РАЗЛИЧИЯ

| Аспект | Раньше (c14af0f) | Сейчас |
|--------|------------------|--------|
| **Что выводится на главной** | КВАРТИРЫ | КОМПЛЕКСЫ (блоки) |
| **API метод для главной** | `getApartments()` | `getObjectsList('', params)` |
| **Источник данных** | `response.data.objects` (квартиры) | `response.data.objects` (блоки) |
| **Проверка image.url** | ❌ Нет | ✅ Есть (напрямую) |
| **Проверка block.image** | ❌ Нет | ✅ Есть (для квартир) |
| **Дополнительные поля** | `photo`, `photo_url`, `image_url`, `gallery` | + `media`, `preview_image`, `cover_image`, `main_image` |
| **Зависимости useEffect** | Без `viewType` | С `viewType` |

---

## 🔍 ПРОБЛЕМА

**Текущая ситуация:** Пользователь сообщает, что выводятся КВАРТИРЫ вместо КОМПЛЕКСОВ.

**Возможные причины:**
1. ❌ Логика `if (selectedObjectType === 'apartments' && viewType === 'list')` не срабатывает
2. ❌ `viewType` не установлен в `'list'` по умолчанию
3. ❌ API возвращает квартиры вместо блоков
4. ❌ Данные извлекаются из неправильного поля ответа

**Проверка:**
- `viewType` по умолчанию: `useState('list')` ✅
- `selectedObjectType` по умолчанию: `useState('apartments')` ✅
- Условие должно срабатывать: `'apartments' === 'apartments' && 'list' === 'list'` ✅

**Вывод:** Логика правильная, но возможно:
- API возвращает не те данные
- Данные извлекаются из неправильного поля
- Нужно проверить консоль браузера (логирование добавлено)

---

## 📝 РЕКОМЕНДАЦИИ

1. ✅ Проверить консоль браузера - там должно быть логирование структуры ответа
2. ✅ Убедиться, что `response.data.objects` содержит блоки, а не квартиры
3. ✅ Проверить, что `getObjectsList('', params)` действительно вызывает `getBlocksSearch`
4. ✅ Возможно, нужно вернуться к логике из коммита c14af0f, но заменить `getApartments` на `getBlocksSearch` для главной страницы

---

**Файлы для проверки:**
- `projects/trendagent/src/pages/ObjectsList.jsx` - логика загрузки
- `projects/trendagent/src/components/ObjectCard.jsx` - логика отображения фото
- `app/Http/Controllers/TrendAgent/TrendSsoController.php` - обработка API запросов
- `app/Services/TrendAgent/TrendSsoApiAuth.php` - метод `getBlocksSearch`
