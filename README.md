# Task Management REST API

Полнофункциональный REST API для управления задачами на Symfony 5.4, Doctrine ORM и PostgreSQL. API предоставляет полный CRUD для задач с дополнительными возможностями: JWT аутентификация, пагинация, фильтрация и логирование ошибок.

## Возможности

**Основной функционал:**
- Полный CRUD для задач (создание, чтение, обновление, удаление)
- Интеграция с PostgreSQL через Doctrine ORM
- JSON формат ответов
- Валидация входных данных (title, description, status)
- Логирование ошибок и исключений
- RESTful дизайн API

**Дополнительные возможности:**
- JWT аутентификация для безопасности API
- Пагинация списка задач
- Фильтрация по статусу (pending, in_progress, completed)
- Юнит-тесты
- Регистрация и аутентификация пользователей

## Технологический стек

- **Framework:** Symfony 5.4 LTS
- **ORM:** Doctrine ORM
- **Database:** PostgreSQL 14+
- **Authentication:** JWT (LexikJWTAuthenticationBundle)
- **Testing:** PHPUnit
- **Logging:** Monolog
- **PHP Version:** 7.4+

## Системные требования

Перед началом убедитесь, что у вас установлено:
- PHP 7.4 или выше
- Composer
- PostgreSQL 14+ (или используйте локальный сервер)
- OpenSSL (для генерации JWT ключей)

## Установка и настройка

### 1. Клонируйте репозиторий

```bash
git clone <repository-url>
cd task-management-api
```

### 2. Установите зависимости

```bash
composer install
```

### 3. Настройте окружение

Создайте файл `.env.dev.local` и настройте подключение к базе данных:

```bash
cat > .env.dev.local << 'EOF'
DATABASE_URL="postgresql://admin:@127.0.0.1:5432/task_management?serverVersion=14&charset=utf8"
JWT_PASSPHRASE=""
EOF
```

**Важно:** Замените `admin` на ваше имя пользователя PostgreSQL.

### 4. Создайте базу данных

```bash
# базу через psql
createdb task_management

# или через Symfony console
php bin/console doctrine:database:create
```

### 5. Создайте схему базы данных

```bash
php bin/console doctrine:schema:create
```

### 6. Сгенерируйте JWT ключи

```bash
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

**Примечание:** используем ключи без пароля для упрощения (`JWT_PASSPHRASE=""`).

### 7. Запустите сервер разработки

```bash
php -S localhost:8001 -t public
```

API будет доступен по адресу `http://localhost:8001`

## API Endpoints

### Аутентификация

#### Регистрация пользователя
```http
POST /api/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "name": "ДАулет Тлеубек"
}
```

#### Вход (Login)
```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

Ответ:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Endpoints задач

**Примечание:** Все endpoints задач требуют аутентификации. Включите JWT токен в заголовок Authorization:

```
Authorization: Bearer ваш_jwt_токен
```

#### Получить все задачи (с пагинацией и фильтрацией)
```http
GET /api/tasks?page=1&limit=10&status=pending
```

Параметры запроса:
- `page` (опционально): Номер страницы (по умолчанию: 1)
- `limit` (опционально): Элементов на странице (по умолчанию: 10, макс: 100)
- `status` (опционально): Фильтр по статусу (pending, in_progress, completed)

Ответ:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Завершить документацию",
      "description": "Написать README на русском",
      "status": "pending",
      "createdAt": "2025-10-20 10:30:00",
      "updatedAt": "2025-10-20 10:30:00",
      "userId": 1
    }
  ],
  "pagination": {
    "total": 50,
    "page": 1,
    "limit": 10,
    "pages": 5
  }
}
```

#### Получить задачу по ID
```http
GET /api/tasks/{id}
```

#### Создать новую задачу
```http
POST /api/tasks
Content-Type: application/json

{
  "title": "Завершить документацию",
  "description": "Написать README на русском",
  "status": "pending"
}
```

#### Обновить задачу
```http
PUT /api/tasks/{id}
Content-Type: application/json

{
  "title": "Обновленное название",
  "description": "Обновленное описание",
  "status": "completed"
}
```

**Примечание:** Все поля опциональны. Обновятся только указанные поля.

#### Удалить задачу
```http
DELETE /api/tasks/{id}
```

## Правила валидации

### Валидация задачи
- **title**: Обязательное, минимум 3 символа, максимум 255 символов
- **description**: Опциональное, текстовое поле
- **status**: Обязательное, должно быть: `pending`, `in_progress` или `completed`

### Валидация пользователя
- **email**: Обязательное, должно быть валидным email, уникальное
- **password**: Обязательное, минимум 6 символов
- **name**: Обязательное

## Формат ошибок

Все ошибки возвращаются в формате:

```json
{
  "success": false,
  "error": "Сообщение об ошибке",
  "details": {
    "field": "Конкретная ошибка валидации"
  }
}
```

HTTP коды состояния:
- `200 OK`: Успешный GET, PUT, DELETE
- `201 Created`: Успешный POST
- `400 Bad Request`: Ошибка валидации или неверный ввод
- `401 Unauthorized`: Отсутствует или неверный JWT токен
- `404 Not Found`: Ресурс не найден
- `500 Internal Server Error`: Ошибка сервера

## Запуск тестов

### Запустить все тесты:
```bash
./vendor/bin/phpunit
```

### Запустить конкретный тестовый класс:
```bash
./vendor/bin/phpunit tests/Controller/TaskControllerTest.php
```

### Запустить тесты с подробным выводом:
```bash
./vendor/bin/phpunit --testdox
```

## Структура проекта

```
task-management-api/
├── bin/                    # Консольные скрипты
├── config/                 # Конфигурационные файлы
│   ├── packages/          # Конфигурация бандлов
│   ├── routes.yaml        # Определение маршрутов
│   └── services.yaml      # Определение сервисов
├── migrations/            # Миграции базы данных
├── public/                # Корневая директория веб-сервера
│   └── index.php         # Точка входа приложения
├── src/
│   ├── Controller/       # API контроллеры
│   │   ├── AuthController.php
│   │   └── TaskController.php
│   ├── Entity/           # Doctrine сущности
│   │   ├── Task.php
│   │   └── User.php
│   ├── Repository/       # Репозитории базы данных
│   │   ├── TaskRepository.php
│   │   └── UserRepository.php
│   └── Kernel.php        # Ядро приложения
├── tests/                # PHPUnit тесты
├── var/                  # Кеш и логи
│   ├── cache/
│   └── log/
├── .env                  # Переменные окружения
├── composer.json         # PHP зависимости
└── README.md            # Этот файл
```

## Логирование

Все ошибки и важные события логируются в:
- Development: `var/log/dev.log`
- Production: `var/log/prod.log`

Логи включают:
- События создания, обновления и удаления задач
- События регистрации пользователей
- Все ошибки и исключения со stack traces
- Ошибки API запросов

## Безопасность

API реализует несколько мер безопасности:
- JWT-based аутентификация
- Хеширование паролей через Symfony password hasher
- CORS конфигурация для cross-origin запросов
- Валидация входных данных на всех endpoints
- Предотвращение SQL инъекций через Doctrine ORM
- Детальное логирование ошибок без раскрытия чувствительной информации

## Схема базы данных

### Таблица Users (пользователи)
- `id`: Primary key (auto-increment)
- `email`: Уникальный email адрес
- `password`: Хешированный пароль
- `name`: Полное имя пользователя
- `roles`: JSON массив ролей пользователя

### Таблица Tasks (задачи)
- `id`: Primary key (auto-increment)
- `user_id`: Foreign key к таблице users (nullable)
- `title`: Название задачи (varchar 255)
- `description`: Описание задачи (text, nullable)
- `status`: Статус задачи (varchar 50)
- `created_at`: Timestamp создания
- `updated_at`: Timestamp последнего обновления

## Тестирование с Postman

Коллекция Postman включена в репозиторий (`postman_collection.json`). Импортируйте её в Postman для быстрого тестирования всех API endpoints.

### Шаги импорта:
1. Откройте Postman
2. Нажмите кнопку "Import"
3. Выберите `postman_collection.json`
4. Коллекция будет добавлена в ваш workspace

Коллекция включает:
- Преднастроенные запросы для всех endpoints
- Переменные окружения для управления токенами
- Примеры тел запросов
- Тесты для автоматической валидации ответов

## Устранение неполадок

### Проблемы с подключением к базе данных
- Убедитесь, что PostgreSQL запущен
- Проверьте учетные данные базы данных в `.env.dev.local`
- Проверьте существование базы: `psql -l`

### Проблемы с JWT аутентификацией
- Убедитесь, что JWT ключи сгенерированы в `config/jwt/`
- Проверьте, что passphrase в `.env` пустой (или совпадает с тем, что использовался при генерации ключей)
- Убедитесь, что токен включен в Authorization header: `Bearer <token>`

### Проблемы с правами доступа
- Убедитесь, что директория `var/` доступна для записи: `chmod -R 777 var/`
- Убедитесь, что директория `config/jwt/` имеет правильные права доступа

## Быстрый старт (для тех, кто торопится)

```bash
# 1. Установка
composer install

# 2. Создание базы (если есть PostgreSQL локально)
createdb task_management

# 3. Создание схемы
php bin/console doctrine:schema:create

# 4. Генерация JWT ключей (без пароля)
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# 5. Создание конфига
echo 'DATABASE_URL="postgresql://admin:@127.0.0.1:5432/task_management?serverVersion=14&charset=utf8"' > .env.dev.local
echo 'JWT_PASSPHRASE=""' >> .env.dev.local

# 6. Запуск сервера
php -S localhost:8001 -t public

# 7. Регистрация
curl -X POST http://localhost:8001/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"test123","name":"Тест"}'

# 8. Логин
curl -X POST http://localhost:8001/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"test123"}'
```

## Совместимость

Проект полностью совместим с:
- ✅ PHP 7.4, 8.0, 8.1, 8.2, 8.3
- ✅ Symfony 5.4 LTS
- ✅ PostgreSQL 12, 13, 14, 15, 16
- ✅ MySQL 5.7+, 8.0+ (с небольшими изменениями в DATABASE_URL)

## Лицензия

Этот проект распространяется под лицензией MIT.



