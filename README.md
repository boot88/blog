# Органайзер на Laravel

Веб-приложение объединяет будильники, задачи, черновики, партнёрские записи и программы. Все пользовательские записи хранятся в MySQL. Интерфейс адаптирован для компьютеров и телефонов.

## Возможности

- будильники с расписанием, звуком и повторами;
- задачи, черновики, партнёрки и программы;
- создание, поиск, редактирование и удаление записей;
- автоматический одноразовый перенос прежних задач из `localStorage` в MySQL;
- адаптивная навигация и формы для экранов от 360 px.

## Требования

- PHP 8.2 или новее с расширением `pdo_mysql`;
- Composer;
- MySQL 8+ или совместимая MariaDB;
- Node.js и npm — только если требуется пересобирать фронтенд-ресурсы.

## Установка

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Создайте базу:

```sql
CREATE DATABASE organizer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Укажите реальные реквизиты в `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=organizer
DB_USERNAME=root
DB_PASSWORD=your_password
APP_TIMEZONE=Asia/Novosibirsk
```

Затем выполните:

```bash
php artisan config:clear
php artisan migrate --force
php artisan serve
```

При первом открытии `/alarms` старые задачи из браузерного ключа `side_tasks_v1` будут отправлены в MySQL. Ключ удаляется только после успешного ответа сервера; повторный импорт не создаёт дубликаты.

## Проверка

```bash
composer test
```

Для размещения в подпапке веб-сервера корнем сайта должна быть директория `public`, а `APP_URL` должен содержать фактический URL приложения.
