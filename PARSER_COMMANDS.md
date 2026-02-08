# 🚀 Команды для запуска парсера TrendAgent

## Краткая справка

### Базовые команды

1. **Простой запуск (комплексы СПб):**
   ```bash
   php artisan trendagent:parse --region=spb --type=complexes
   ```

2. **Все типы объектов:**
   ```bash
   php artisan trendagent:parse --region=spb --type=all --details --save-raw
   ```

3. **Конкретный тип:**
   ```bash
   php artisan trendagent:parse --region=spb --type=apartments --details --save-raw
   php artisan trendagent:parse --region=spb --type=parkings --details --save-raw
   php artisan trendagent:parse --region=spb --type=houses --details --save-raw
   php artisan trendagent:parse --region=spb --type=plots --details --save-raw
   php artisan trendagent:parse --region=spb --type=commercial --details --save-raw
   php artisan trendagent:parse --region=spb --type=contractors --details --save-raw
   ```

### С ограничениями

```bash
# Ограничить количество
php artisan trendagent:parse --region=spb --type=complexes --limit=100

# Продолжить с определённого места
php artisan trendagent:parse --region=spb --type=apartments --offset=1000 --limit=500
```

### Параметры

| Параметр   | Описание           | Пример           |
|-----------|--------------------|------------------|
| `--region` | Регион (spb, msk)  | `--region=spb`   |
| `--type`   | Тип объектов       | `--type=apartments` |
| `--limit`  | Лимит объектов     | `--limit=100`    |
| `--offset` | Смещение           | `--offset=100`   |
| `--details` | Детальные страницы | `--details`     |
| `--save-raw` | Сохранять сырые данные | `--save-raw` |
| `--images` | Скачивать изображения | `--images`   |

### Типы объектов

| Тип           | Описание     |
|---------------|--------------|
| `complexes`   | Комплексы    |
| `apartments`  | Квартиры     |
| `parkings`    | Паркинги     |
| `houses`      | Дома         |
| `plots`       | Участки      |
| `commercial`  | Коммерция    |
| `contractors` | Подрядчики   |
| `all`         | Все типы     |

### Через веб-интерфейс

**URL:** https://api.siteaccess.ru/trendagent/parser

- **▶️ Запустить** — обычный парсинг (по умолчанию: комплексы СПб).
- **🚀 Полный парсинг + Анализ** — полный парсинг с автоматическим анализом.

---

## 📋 Основные команды

### 1. Базовая команда парсинга

```bash
php artisan trendagent:parse
```

### 2. С указанием региона

```bash
php artisan trendagent:parse --region=spb
php artisan trendagent:parse --region=msk
```

### 3. С указанием типа объектов

```bash
# Один тип
php artisan trendagent:parse --region=spb --type=complexes
php artisan trendagent:parse --region=spb --type=apartments
php artisan trendagent:parse --region=spb --type=parkings
php artisan trendagent:parse --region=spb --type=houses
php artisan trendagent:parse --region=spb --type=plots
php artisan trendagent:parse --region=spb --type=commercial
php artisan trendagent:parse --region=spb --type=contractors

# Все типы
php artisan trendagent:parse --region=spb --type=all
```

### 4. С ограничениями

```bash
# Ограничить количество объектов
php artisan trendagent:parse --region=spb --type=complexes --limit=100

# Начать с определенного смещения
php artisan trendagent:parse --region=spb --type=complexes --offset=100
```

### 5. С опциями

```bash
# Парсить детальные страницы
php artisan trendagent:parse --region=spb --type=complexes --details

# Сохранять сырые данные
php artisan trendagent:parse --region=spb --type=complexes --save-raw

# Скачивать изображения локально
php artisan trendagent:parse --region=spb --type=complexes --images

# Все опции вместе
php artisan trendagent:parse --region=spb --type=all --details --save-raw --images
```

---

## 🎯 Примеры использования

### Пример 1: Парсинг всех комплексов в СПб

```bash
php artisan trendagent:parse --region=spb --type=complexes --details --save-raw
```

### Пример 2: Парсинг первых 50 квартир

```bash
php artisan trendagent:parse --region=spb --type=apartments --limit=50 --details
```

### Пример 3: Полный парсинг всех типов объектов

```bash
php artisan trendagent:parse --region=spb --type=all --details --save-raw
```

### Пример 4: Продолжить парсинг с определенного места

```bash
php artisan trendagent:parse --region=spb --type=apartments --offset=1000 --limit=500
```

---

## 🌐 Запуск через веб-интерфейс

### API Endpoints

#### 1. Обычный парсинг

```bash
POST /api/trendagent/parser/start
Content-Type: application/json

{
    "region": "spb",
    "type": "complexes",
    "limit": 100000,
    "details": true,
    "save_raw": true
}
```

#### 2. Полный парсинг + Анализ

```bash
POST /api/trendagent/parser/start-full
Content-Type: application/json

{
    "region": "spb",
    "limit": 100000,
    "details": true,
    "save_raw": true,
    "auto_analyze": true
}
```

#### 3. Проверить статус

```bash
GET /api/trendagent/parser/status
```

#### 4. Получить логи

```bash
GET /api/trendagent/parser/logs?lines=200
```

#### 5. Получить статистику

```bash
GET /api/trendagent/parser/statistics
```

#### 6. Остановить парсер

```bash
POST /api/trendagent/parser/stop
```

---

## 📝 Параметры команды

| Параметр | Описание | По умолчанию | Пример |
|----------|----------|--------------|--------|
| `--region` | Регион для парсинга | `spb` | `--region=msk` |
| `--type` | Тип объектов | `all` | `--type=apartments` |
| `--limit` | Лимит объектов | `0` (без лимита) | `--limit=100` |
| `--offset` | Смещение для пагинации | `0` | `--offset=100` |
| `--details` | Парсить детальные страницы | `true` | `--details` |
| `--save-raw` | Сохранять сырые данные | `true` | `--save-raw` |
| `--images` | Скачивать изображения | `false` | `--images` |

---

## 🔍 Типы объектов

- `complexes` - Комплексы (ЖК)
- `apartments` - Квартиры
- `parkings` - Паркинги (машиноместа)
- `houses` - Дома
- `plots` - Участки
- `commercial` - Коммерческая недвижимость
- `contractors` - Подрядчики (проекты домов)
- `all` - Все типы

---

## 📊 Регионы

- `spb` - Санкт-Петербург
- `msk` - Москва
- И другие регионы (зависит от API)

---

## 💡 Полезные команды

### Проверить статус парсера

```bash
php artisan list | grep trendagent
```

### Просмотреть логи

```bash
tail -f storage/logs/parser_*.log
```

### Очистить старые логи

```bash
rm -f storage/logs/parser_*.log
```

### Проверить сохраненные данные

```bash
ls -la storage/trendagent/parsing/spb/raw/
ls -la storage/trendagent/parsing/spb/details/
```

---

## ⚠️ Важно

1. **Права доступа:** Убедитесь, что у веб-сервера есть права на запись в `storage/trendagent/`
2. **PID файл:** Если парсер не запускается, проверьте и удалите `storage/app/parser_pid.txt`
3. **Логи:** Все логи сохраняются в `storage/logs/parser_*.log`

---

## 🚀 Быстрый старт

```bash
# 1. Простой запуск (все комплексы СПб)
php artisan trendagent:parse --region=spb --type=complexes

# 2. Полный парсинг всех типов
php artisan trendagent:parse --region=spb --type=all --details --save-raw

# 3. Через веб-интерфейс
# Откройте: https://api.siteaccess.ru/trendagent/parser
# Нажмите кнопку "▶️ Запустить" или "🚀 Полный парсинг + Анализ"
```
