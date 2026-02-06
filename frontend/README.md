# AL API Gateway - React Frontend

React приложение для документации AL API Gateway с поддержкой RU/EN языков.

## Технологии

- React 18
- React Router 6
- Vite
- i18next (i18n)
- Axios

## Установка

```bash
cd frontend
npm install
```

## Разработка

```bash
npm run dev
```

Приложение будет доступно на http://localhost:3000

## Сборка

```bash
npm run build
```

Собранные файлы будут в `public/react/`

## Структура

```
frontend/
├── src/
│   ├── components/      # React компоненты
│   ├── pages/            # Страницы приложения
│   ├── routes/           # Роутинг
│   ├── services/         # API сервисы
│   ├── i18n/             # Локализация
│   └── App.jsx           # Главный компонент
├── public/               # Статические файлы
└── package.json
```

## Страницы

- `/` - Главная
- `/guide` - Документация API
- `/streaming` - Streaming Guide
- `/multipart` - Multipart Upload
- `/parameters` - Model Parameters
- `/errors` - Error Handling
- `/swagger` - Swagger UI (редирект)

## Языки

- Русский (по умолчанию)
- English

Переключение через компонент LanguageSwitcher (правый верхний угол).
