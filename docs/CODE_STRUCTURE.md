# Code Structure

## `src/Helpers`

### `Database.php`
Singleton database wrapper. Loads `.env`, creates the PDO connection, and exposes transaction helpers.

### `Sanitizer.php`
Cleans user input before validation or database usage. Uses `mixed` parameters because input may come from `$_POST`, `$_GET`, `$_SESSION`, or database rows.

### `Validator.php`
Validates username, email, password, required fields, and class names.

## `src/Controllers`

### `UserController.php`
Handles registration and login validation before calling `UserModel`.

### `ClassController.php`
Handles class validation before calling `ClassModel`.

### `CourseController.php`
Placeholder controller for future course features.

## `src/Models`

### `UserModel.php`
Contains account and user database operations.

### `ClassModel.php`
Contains class, post, activity, attachment, submission, unsubmission, and grading database operations.

## `src/Utils`

### `EnvParser.php`
Reads `.env` values and loads them into environment variables.

### `Upload.php`
Handles file upload validation, file movement, submission uploads, and file deletion.

## `src/APIs`

### `UserAPI.php`
JSON API for checking username, email, and class name validation/availability.

## `assets/pages`

Contains the main browser pages. These files combine page-level request handling and HTML rendering.
