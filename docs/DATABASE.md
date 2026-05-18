# Database Documentation

## Main tables

### `Account`
Stores login credentials.

Important columns:

- `account_id`
- `username`
- `email`
- `password`

### `Users`
Stores profile information linked to `Account`.

Important columns:

- `user_id`
- `account_id`
- `first_name`
- `last_name`
- `status`

### `Classes`
Stores class details.

Important columns:

- `class_id`
- `class_name`
- `class_desc`
- `class_code`
- `created_by`
- `status`

### `Class_User`
Connects users to classes and stores their role.

Important columns:

- `class_user_id`
- `class_id`
- `user_id`
- `role`

### `Post`
Stores announcements, activities, and materials.

Important columns:

- `post_id`
- `class_id`
- `postedBy`
- `type`
- `title`
- `description`
- `due_date`

### `Activity`
Stores activity-specific data.

Important columns:

- `activity_id`
- `post_id`
- `max_score`
- `allow_late`

### `Submission`
Stores student activity submission records.

Important columns:

- `submission_id`
- `class_user_id`
- `activity_id`
- `grade`
- `submitted_at`
- `status`

### `Submission_Attachment`
Stores files uploaded by students for activity submissions.

Important columns:

- `submission_attachment_id`
- `submission_id`
- `file_name`
- `file_path`
- `file_type`
- `uploaded_at`

## Unsubmit history behavior

The app keeps the `Submission` row when a student unsubmits. This makes the action traceable while removing uploaded files. The row is updated to:

```sql
status = 'unsubmitted', submitted_at = NULL
```

When the student submits again, the same record is updated back to:

```sql
status = 'submitted'
```
