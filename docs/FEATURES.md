# Feature Documentation

## Authentication

Users can register and log in. Passwords are hashed before being saved.

## Class management

Teachers can create classes. Students can join using a class code. Teachers can archive classes.

## Class stream

The class stream displays posts such as announcements and assignments. Posts may include attachments.

## Assignments

Teachers can create assignments with descriptions, due dates, points, and optional files.

## Submissions

Students can submit assignment files. Supported file types are handled by `Upload.php`.

## Unsubmit

Students can unsubmit uploaded work. This removes uploaded files and file records, but keeps the submission row for history.

## Grading

Teachers can view submissions and save numeric grades per student.

## File uploads

Teacher files are stored as post attachments. Student files are stored as submission attachments.
