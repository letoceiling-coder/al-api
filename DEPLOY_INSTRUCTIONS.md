# 📦 ИНСТРУКЦИЯ: Развертывание кнопки "Полный парсинг + Анализ" на сервер

**Дата:** 2026-02-08  
**Проблема:** Файлы собраны локально, но кнопка не отображается на сервере

---

## ✅ ЛОКАЛЬНО ВСЕ ГОТОВО

Файлы собраны и находятся в:
- `public/trendagent/index.html` (обновлен с версионированием `?v=2`)
- `public/trendagent/assets/index-BtU4Sbb5.js` (обновлен 08.02.2026 14:10:22)
- `public/trendagent/assets/index-DXWCK6sL.css` (обновлен)
- `public/trendagent/assets/react-vendor-DdVQdU_w.js`
- `public/trendagent/assets/axios-vendor-D5GkNzM3.js`

---

## 🚀 РАЗВЕРТЫВАНИЕ НА СЕРВЕР

### Вариант 1: Через Git (если используется)

```bash
# На локальной машине
git add public/trendagent/
git commit -m "Добавлена кнопка 'Полный парсинг + Анализ'"
git push

# На сервере
cd /var/www/AL
git pull
```

---

### Вариант 2: Прямая загрузка через FTP/SFTP

Загрузите следующие файлы на сервер:

**Обязательные файлы:**
1. `public/trendagent/index.html` → `/var/www/AL/public/trendagent/index.html`
2. `public/trendagent/assets/index-BtU4Sbb5.js` → `/var/www/AL/public/trendagent/assets/index-BtU4Sbb5.js`
3. `public/trendagent/assets/index-DXWCK6sL.css` → `/var/www/AL/public/trendagent/assets/index-DXWCK6sL.css`

**Опционально (если изменились):**
4. `public/trendagent/assets/react-vendor-DdVQdU_w.js`
5. `public/trendagent/assets/axios-vendor-D5GkNzM3.js`

---

### Вариант 3: Через SCP (SSH)

```bash
# С локальной машины (Windows PowerShell или WSL)
scp public/trendagent/index.html user@server:/var/www/AL/public/trendagent/
scp public/trendagent/assets/index-BtU4Sbb5.js user@server:/var/www/AL/public/trendagent/assets/
scp public/trendagent/assets/index-DXWCK6sL.css user@server:/var/www/AL/public/trendagent/assets/
```

---

### Вариант 4: Пересборка на сервере

Если на сервере есть Node.js и доступ к исходникам:

```bash
# На сервере
cd /var/www/AL/projects/trendagent
npm install  # если нужно
npm run build
```

---

## 🔍 ПРОВЕРКА НА СЕРВЕРЕ

После загрузки файлов проверьте:

1. **Проверьте наличие файлов:**
```bash
ls -la /var/www/AL/public/trendagent/assets/
ls -la /var/www/AL/public/trendagent/index.html
```

2. **Проверьте дату модификации:**
```bash
stat /var/www/AL/public/trendagent/assets/index-BtU4Sbb5.js
```
Должна быть дата **08.02.2026 14:10** или новее.

3. **Проверьте содержимое index.html:**
```bash
cat /var/www/AL/public/trendagent/index.html | grep "v=2"
```
Должна быть строка с `?v=2`.

4. **Проверьте права доступа:**
```bash
chmod 644 /var/www/AL/public/trendagent/assets/*.js
chmod 644 /var/www/AL/public/trendagent/assets/*.css
chmod 644 /var/www/AL/public/trendagent/index.html
```

---

## 🧹 ОЧИСТКА КЭША НА СЕРВЕРЕ

Если используется Nginx/Apache с кэшированием:

```bash
# Nginx
sudo systemctl reload nginx

# Apache
sudo systemctl reload apache2
# или
sudo service apache2 reload
```

---

## ✅ ПРОВЕРКА В БРАУЗЕРЕ

После загрузки файлов:

1. Откройте https://api.siteaccess.ru/trendagent/parser
2. Нажмите `Ctrl + Shift + R` (жесткая перезагрузка)
3. Или откройте с параметром: `https://api.siteaccess.ru/trendagent/parser?v=2`

Должны быть видны 3 кнопки:
- ▶️ Запустить
- 🚀 Полный парсинг + Анализ ← **НОВАЯ КНОПКА**
- ⏹️ Остановить

---

## 🐛 ЕСЛИ КНОПКА ВСЕ ЕЩЕ НЕ ВИДНА

1. **Проверьте консоль браузера (F12 → Console)** на наличие ошибок
2. **Проверьте Network (F12 → Network)** - загружаются ли новые файлы
3. **Проверьте, что файлы действительно обновлены на сервере:**
```bash
# На сервере
grep -r "start-full" /var/www/AL/public/trendagent/assets/index-*.js
```
Должна быть найдена строка с `start-full`.

---

## 📝 ПРИМЕЧАНИЯ

- Файлы собраны локально и готовы к загрузке
- Версионирование `?v=2` добавлено для обхода кэша браузера
- Все необходимые изменения в коде уже внесены
- Backend маршрут `/api/trendagent/parser/start-full` уже существует

---

**Статус:** ✅ **ГОТОВО К РАЗВЕРТЫВАНИЮ**  
**Дата:** 2026-02-08
