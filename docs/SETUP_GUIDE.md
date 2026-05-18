# Setup Guide

## 1. Start local server

Use XAMPP, Laragon, or another local PHP stack. Start Apache and MySQL/MariaDB.

## 2. Install dependencies

From the project root:

```bash
composer install
composer dump-autoload
```

## 3. Create database

Import the schema:

```bash
mysql -u root -p < database.sql
```

The schema creates `EduRIft_db` and the required tables.

## 4. Configure environment

Edit `.env` to match your local database settings.

Example for XAMPP default root user with no password:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=EduRIft_db
DB_USER=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_DRIVER=mysql
```

## 5. Run the app

Open:

```text
http://localhost/Lms/assets/pages/login.php
```

## 6. Test basic flow

1. Register a teacher account.
2. Create a class.
3. Register a student account.
4. Join the class using the class code.
5. Create an activity as the teacher.
6. Submit and unsubmit files as the student.
7. Grade the submission as the teacher.
