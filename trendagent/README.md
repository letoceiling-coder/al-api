# TrendAgent Frontend

React приложение для работы с TrendAgent API.

## Установка

```bash
npm install
```

## Разработка

```bash
npm run dev
```

Приложение будет доступно по адресу `http://localhost:3001`

## Сборка

```bash
npm run build
```

Собранные файлы будут в `public/trendagent/`

## Структура проекта

```
trendagent/
├── src/
│   ├── components/       # React компоненты
│   │   ├── detail/      # Компоненты детальной информации
│   │   ├── ObjectCard.jsx
│   │   ├── ObjectTypeFilter.jsx
│   │   └── SearchFilters.jsx
│   ├── pages/           # Страницы
│   │   ├── ObjectsList.jsx
│   │   └── ObjectDetail.jsx
│   ├── services/        # API сервисы
│   │   └── api.js
│   ├── App.jsx
│   └── main.jsx
├── public/
├── index.html
├── package.json
└── vite.config.js
```
