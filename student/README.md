# Student Guide

This guide covers student-side pages and actions.

## Access

- Student login: `http://localhost/studentcoursehub-main/student/controller/student-login.php`
- Student signup: `http://localhost/studentcoursehub-main/student/controller/student-signup.php`

## Main Student Pages

- Student dashboard: `http://localhost/studentcoursehub-main/student/view/student-dashboard.php`
- Profile: `http://localhost/studentcoursehub-main/student/view/profile.php`
- Programme details: `http://localhost/studentcoursehub-main/student/view/programme-details.php`
- Register interest: `http://localhost/studentcoursehub-main/student/view/register-interest.php`

## Folder Responsibilities

- `student/controller`: Student authentication and actions.
- `student/view`: Student UI pages and displays.

## Notes

- Student flows include register/unregister interest and profile update.
- Password reset starts from `forgot-password.php?mode=student`.
