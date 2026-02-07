# Настройка Git для проекта AL API

## Локальная настройка (C:\OSPanel\domains\AL)

✅ **Уже настроено:**
- Git репозиторий инициализирован
- Remote origin добавлен: `https://github.com/letoceiling-coder/al-api.git`
- Первый коммит создан и отправлен
- Ветка `main` настроена

### Команды для работы:

```bash
# Проверить статус
git status

# Добавить изменения
git add .

# Создать коммит
git commit -m "Описание изменений"

# Отправить на GitHub
git push origin main

# Получить изменения
git pull origin main
```

---

## Настройка на сервере (89.169.39.244)

Для работы с приватным репозиторием на сервере нужно настроить аутентификацию.

### Вариант 1: Использование Personal Access Token (рекомендуется)

1. Создайте Personal Access Token на GitHub:
   - Settings → Developer settings → Personal access tokens → Tokens (classic)
   - Generate new token (classic)
   - Выберите права: `repo` (полный доступ к приватным репозиториям)
   - Скопируйте токен

2. На сервере настройте Git:

```bash
ssh root@89.169.39.244

# Добавить директорию в safe.directory
git config --global --add safe.directory /var/www/AL

# Настроить remote с токеном
cd /var/www/AL
git remote set-url origin https://YOUR_TOKEN@github.com/letoceiling-coder/al-api.git

# Или использовать переменную окружения
export GIT_ASKPASS=echo
export GIT_USERNAME=YOUR_TOKEN
export GIT_PASSWORD=x-oauth-basic
```

### Вариант 2: Использование SSH ключей

1. Создайте SSH ключ на сервере:

```bash
ssh root@89.169.39.244
ssh-keygen -t ed25519 -C "your_email@example.com"
cat ~/.ssh/id_ed25519.pub
```

2. Добавьте публичный ключ в GitHub:
   - Settings → SSH and GPG keys → New SSH key
   - Вставьте содержимое `~/.ssh/id_ed25519.pub`

3. На сервере измените remote на SSH:

```bash
cd /var/www/AL
git remote set-url origin git@github.com:letoceiling-coder/al-api.git
git fetch origin
git reset --hard origin/main
```

### Вариант 3: Синхронизация через rsync (без Git на сервере)

Если не нужен Git на сервере, можно синхронизировать файлы:

```bash
# С локальной машины
rsync -avz --exclude '.git' --exclude 'node_modules' --exclude 'vendor' \
  C:\OSPanel\domains\AL\ root@89.169.39.244:/var/www/AL/
```

---

## Текущее состояние

### Локально (C:\OSPanel\domains\AL):
- ✅ Git инициализирован
- ✅ Remote настроен
- ✅ Проект отправлен в GitHub
- ✅ Ветка: `main`

### На сервере (/var/www/AL):
- ⚠️ Git инициализирован, но нужна аутентификация для pull
- ⚠️ Файлы проекта уже есть на сервере
- ⚠️ Нужно настроить доступ к приватному репозиторию

---

## Рекомендации

1. **Для разработки:** Используйте локальный репозиторий в `C:\OSPanel\domains\AL`
2. **Для деплоя:** Настройте один из вариантов аутентификации на сервере
3. **Для безопасности:** Используйте SSH ключи или переменные окружения для токенов

---

## Полезные команды

```bash
# Проверить remote
git remote -v

# Изменить remote URL
git remote set-url origin https://github.com/letoceiling-coder/al-api.git

# Посмотреть историю коммитов
git log --oneline

# Создать новую ветку
git checkout -b feature/new-feature

# Слить изменения
git merge feature/new-feature
```
