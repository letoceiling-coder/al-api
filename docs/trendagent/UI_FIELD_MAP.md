# Карта полей UI → API

**Дата:** 2026-02-14  
**Назначение:** Детальное сопоставление полей ответа API с компонентами UI.

---

## ObjectCard (карточка в каталоге)

| UI элемент | Поля API | Примечание |
|------------|----------|------------|
| Ссылка | `_id`, `id`, `guid` | id в path, guid в ?guid= |
| Изображение | `images[0]`, `image`, `renderer[0]`, `gallery[0]`, `photo`, `preview_image`, `cover_image`, `main_image` | path+file_name → URL |
| Заголовок | `name`, `title`, `block_name`, `village_name`, `village.name` | |
| Адрес | `address` | |
| Цена | `price`, `min_price`, `min_prices[0]`, `price_from` | min_prices: price/value/formatted_value/label/unit |
| Срок сдачи | `deadline` | string, date, или массив |
| Счётчик | `apart_count` (blocks), `places_count` (parkings) | |

---

## ObjectHeader (заголовок детальной страницы)

| UI элемент | Поля unified.data | |
|------------|-------------------|--|
| Название | `name` | |
| Адрес | `address` | |
| Описание | `description`, `about` | |
| Цена от | `min_prices[0].value`, `min_price`, `price_from` | |
| Срок сдачи | `deadline` | |
| Галерея | `renderer`, `images`, `image` | url, urlFull |

---

## ApartmentsTable (квартиры в ЖК)

| UI элемент | Поля item | |
|------------|-----------|---|
| Номер | `number` | |
| Комнаты | `rooms` | |
| Площадь | `area_total`, `area`, `privArea` | |
| Этаж | `floor` | |
| Цена | `base_price`, `price` | |
| План | `plan`, `plan_image`, `image` | |
| Очередь/корпус/секция | `queue`, `building_name`, `section_name` | |
| Срок сдачи | `deadline` | |

---

## FlatPassport / FlatDetail (детали квартиры)

| UI элемент | Поля | |
|------------|------|--|
| Номер | `number`, `apartment_number` | |
| Этаж / этажность | `floor`, `total_floors`, `block.floors` | |
| Секция/корпус | `section_name`, `section`, `building_name`, `building`, `corpus` | |
| Площади | `privArea`, `area`, `area_total`, `calculated_area`, `kitchenArea`, `livingArea` | |
| Балкон/отделка/вид | `balcony_type`, `finishing_name`, `view_type` | |
| Цены | `base_price`, `price`, `full_price` | |
| Статус | `status`, `status.name`, `booking_status`, `is_booked` | |
| План/фото | `plan`, `plan_image`, `images`, `gallery_images` | |

---

## Blade db modal (getApartmentDetails)

| UI элемент | Поле data | |
|------------|-----------|---|
| План | `plan_image_url`, `images[0]`, `raw_data.plan_image` | |
| Номер | `number` | |
| Комнаты | `rooms` | |
| Площади | `area_total`, `area_living`, `area_kitchen` | |
| Этаж | `floor` | |
| Цены | `price_base`, `price_full`, `price_per_sqm` | |
| Статус | `is_booked` | |

---

## SearchFilters

| Фильтр | Параметр API | Тип |
|--------|--------------|-----|
| Город | `city` | string (id) |
| Поиск | `text` | string |
| Комнаты (квартиры) | `room` | array[int] 1..6 |
| Цена от/до | `price_from`, `price_to` | int |
| Площадь от/до | `area_from`, `area_to` | float |
| Тип паркинга | `parking_type` | string |
| Назначение (коммерция) | `purpose` | string |
| Сортировка | `sort` | price, deadline, name |
| Направление | `sort_order` | asc, desc |

---

## ImageUtils (форматы изображений)

UI принимает:
- Строка URL (http/https)
- Объект: `url`, `url_full`, `thumbnail`, `full`, `path`+`file_name`
- Путь: `/path` → `https://selcdn.trendagent.ru` + path
- path+file_name → `https://selcdn.trendagent.ru/images/{path}/m_{file_name}` (thumbnail)
