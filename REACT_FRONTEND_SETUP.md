# 🚀 React Frontend Setup Guide

**Date:** 2026-02-06  
**Status:** ✅ **CREATED**

---

## 📦 Что Создано

### Структура Проекта:
```
frontend/
├── src/
│   ├── components/
│   │   ├── Navigation.jsx          # Навигация
│   │   ├── Navigation.css
│   │   ├── LanguageSwitcher.jsx    # Переключатель языков
│   │   └── LanguageSwitcher.css
│   ├── pages/
│   │   ├── Home.jsx                # Главная страница
│   │   ├── Home.css
│   │   ├── Documentation.jsx      # API документация
│   │   ├── Documentation.css
│   │   ├── StreamingGuide.jsx      # Streaming guide
│   │   ├── MultipartGuide.jsx      # Multipart upload
│   │   ├── ParametersGuide.jsx     # Model parameters
│   │   ├── ErrorsGuide.jsx         # Error handling
│   │   ├── Swagger.jsx             # Swagger redirect
│   │   └── Page.css                # Общие стили
│   ├── routes/
│   │   └── AppRoutes.jsx           # React Router config
│   ├── services/
│   │   └── api.js                  # Axios API client
│   ├── i18n/
│   │   ├── config.js               # i18next config
│   │   └── locales/
│   │       ├── ru.json              # Русский
│   │       └── en.json              # English
│   ├── App.jsx                      # Главный компонент
│   ├── App.css
│   ├── main.jsx                     # Entry point
│   └── index.css                    # Global styles
├── public/
│   └── vite.svg
├── package.json
├── vite.config.js
├── .eslintrc.cjs
├── .gitignore
└── README.md
```

---

## 🎯 Features

### ✅ Реализовано:
1. **React 18** с Vite
2. **React Router 6** для навигации
3. **i18next** для RU/EN поддержки
4. **Axios** для API запросов
5. **7 страниц документации**
6. **Responsive design**
7. **Purple gradient theme**
8. **Navigation компонент**
9. **Language switcher**

---

## 📄 Страницы

| Route | Компонент | Описание |
|-------|-----------|----------|
| `/` | Home | Главная страница |
| `/guide` | Documentation | API документация |
| `/streaming` | StreamingGuide | SSE streaming |
| `/multipart` | MultipartGuide | File upload |
| `/parameters` | ParametersGuide | Model parameters |
| `/errors` | ErrorsGuide | Error handling |
| `/swagger` | Swagger | Redirect to Swagger UI |

---

## 🌐 Языки

### Поддержка:
- ✅ **Русский** (по умолчанию)
- ✅ **English**

### Локализация:
- Навигация
- Заголовки страниц
- Общие элементы UI
- Сообщения

### Хранение:
- `localStorage` для сохранения выбора
- Автоопределение языка браузера

---

## 🔧 Установка и Запуск

### 1. Установка зависимостей:
```bash
cd frontend
npm install
```

### 2. Разработка:
```bash
npm run dev
```
Приложение будет на http://localhost:3000

### 3. Сборка для production:
```bash
npm run build
```
Файлы будут в `public/react/`

### 4. Деплой на сервер:
```bash
# После сборки
cd ..
git add public/react
git commit -m "Build React app"
git push origin main

# На сервере
ssh root@89.169.39.244
cd /var/www/AL
git pull origin main
```

---

## 🎨 Design System

### Colors:
```css
--primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
--primary-color: #667eea;
--secondary-color: #764ba2;
--text-primary: #333;
--text-secondary: #666;
--bg-primary: #ffffff;
--bg-secondary: #f5f7fa;
```

### Components:
- Navigation (sticky, purple gradient)
- Language Switcher (fixed top-right)
- Cards (hover effects)
- Code blocks (dark theme)
- Buttons (gradient)

---

## 🔌 API Integration

### API Client:
```javascript
import api from './services/api'

// GET request
const response = await api.get('/v1/test')

// POST request
const response = await api.post('/v1/ai/process', {
  provider: 'gemini',
  model: 'gemini-1.5-pro',
  prompt: 'Hello!'
})
```

### Token Management:
- Токен хранится в `localStorage` как `api_token`
- Автоматически добавляется в заголовки запросов
- Можно добавить UI для ввода токена

---

## 📱 Responsive Design

### Breakpoints:
- Desktop: > 768px (full navigation)
- Mobile: < 768px (icons only, stacked layout)

### Mobile Features:
- Скрыт текст в навигации (только иконки)
- Вертикальная компоновка
- Адаптивные карточки
- Touch-friendly кнопки

---

## 🚀 Next Steps

### Для Запуска:
1. ✅ Установить зависимости: `npm install`
2. ✅ Запустить dev server: `npm run dev`
3. ✅ Проверить все страницы
4. ✅ Протестировать переключение языков
5. ✅ Собрать для production: `npm run build`

### Опциональные Улучшения:
- [ ] Добавить страницу входа/регистрации
- [ ] Добавить форму для тестирования API
- [ ] Добавить примеры кода с подсветкой синтаксиса
- [ ] Добавить dark mode
- [ ] Добавить поиск по документации
- [ ] Добавить breadcrumbs

---

## 📊 Статистика

```
Files Created: 25+
Components: 8
Pages: 7
Languages: 2
Routes: 7
Lines of Code: ~1500+
```

---

## 🔗 URLs

После деплоя:
- React App: https://api.siteaccess.ru/react/
- Главная: https://api.siteaccess.ru/react/
- Документация: https://api.siteaccess.ru/react/guide

---

## ✅ Checklist

- [x] React app structure
- [x] Vite configuration
- [x] React Router setup
- [x] i18next configuration
- [x] Navigation component
- [x] Language switcher
- [x] All 7 pages
- [x] API client
- [x] Responsive design
- [x] Styling
- [x] Route configuration in Laravel
- [x] Git commit

---

## 🎉 Готово!

**React приложение создано и готово к разработке!**

**Следующий шаг:** Установить зависимости и запустить dev server.

---

**Status:** ✅ COMPLETE  
**Next:** `cd frontend && npm install && npm run dev`
