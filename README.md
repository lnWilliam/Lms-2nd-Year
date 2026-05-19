# EduRift / MyLMS

EduRift is a simple PHP learning management system. It supports user registration/login, class creation and joining, class streams, announcements, activities, file attachments, activity submission, unsubmission, and teacher grading.

## Project structure

```text
Lms/
├── assets/
│   ├── css/              # Page styles
│   ├── js/               # Front-end scripts
│   └── pages/            # Main web pages/controllers for the UI
├── src/
│   ├── APIs/             # JSON endpoints
│   ├── Controllers/      # Validation/process controllers
│   ├── Helpers/          # Database, sanitizer, validator helpers
│   ├── Models/           # Database query models
│   └── Utils/            # Upload and environment utilities
├── database.sql          # Database schema
├── composer.json         # Composer autoload config
└── .env                  # Database configuration
```

## Requirements

- PHP 8.0 or newer
- MySQL or MariaDB
- Composer
- XAMPP, Laragon, or a similar local PHP stack

## Setup

1. Put the project folder inside your web server root, for example:

   ```text
   C:\xampp\htdocs\Lms
   ```

2. Install Composer dependencies/autoload files:

   ```bash
   composer install
   composer dump-autoload
   ```

3. Import the database:

   ```bash
   mysql -u root -p < database.sql
   ```

4. Configure `.env`:

   ```env
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=EduRIft_db
   DB_USER=root
   DB_PASSWORD=
   DB_CHARSET=utf8mb4
   DB_DRIVER=mysql
   ```

5. Open the app in your browser:

   ```text
   http://localhost/Lms/assets/pages/login.php
   ```

## Main pages

| File | Purpose |
|---|---|
| `assets/pages/login.php` | Login and registration page |
| `assets/pages/home.php` | Dashboard and class create/join page |
| `assets/pages/class.php` | Class stream, announcements, activities, students list |
| `assets/pages/activity.php` | Activity details, student submission/unsubmission, teacher grading |
| `assets/pages/edit_class.php` | Teacher class edit page |
| `assets/pages/delete_class.php` | Teacher class archive page |
| `assets/pages/account_settings.php` | User account details page |
| `assets/pages/logout.php` | Ends the user session |

## Backend flow

### Registration/login

`login.php` sends form data to `UserController`, which validates and sanitizes input using `Validator` and `Sanitizer`. `UserModel` handles database queries for account creation and login lookup.

### Classes

`home.php` lets users create or join classes. `ClassController` validates class data, and `ClassModel` inserts or fetches class records.

### Activities

Teachers create activities in `class.php`. Students open `activity.php` to upload files. Teachers use the same page to view submissions and save grades.

### Unsubmit behavior

When a student unsubmits, the uploaded file records are removed from `Submission_Attachment`, physical files are deleted through `Upload`, and the `Submission` row is kept for history by setting `status = 'unsubmitted'` and `submitted_at = NULL`.

## Strict types note

All non-vendor PHP files use:

```php
```

Because of strict types, helper functions cast values from `$_POST`, `$_GET`, `$_SESSION`, and database rows before using string functions like `trim()`.

## Common troubleshooting

### `Class not found`

Run:

```bash
composer dump-autoload
```

Also make sure file names match class names, for example `src/Utils/Upload.php` should contain `class Upload`.

### Database connection failed

Check `.env`, database name, username, password, and whether MySQL/MariaDB is running.

### Uploads do not appear

Make sure the `documents/` and `documents/submissions/` folders are writable by PHP.

### Strict types `trim()` error

This happens when an integer is passed to `trim()`. Use the provided `Sanitizer` methods, which cast mixed input safely before trimming.


## Documentation Standard

The backend PHP classes include class-level and method-level PHPDoc documentation. Class blocks describe each class responsibility and include `@package`, `@author Charlo Marco`, and `@since 2026-05-17`. Method blocks describe what each method does and why it exists, with documented `@param`, `@return`, and `@throws` tags.
