# Staff Guide

This guide covers staff-side pages and actions.

## Access

- Staff/Admin login: `http://localhost/studentcoursehub-main/staff/controller/staff-login.php`

## Main Staff Pages

- Staff dashboard: `http://localhost/studentcoursehub-main/staff/view/staff-dashboard.php`

## Folder Responsibilities

- `staff/controller`: Staff/Admin login and staff auth-related actions.
- `staff/view`: Staff dashboard and staff-facing UI.

## Notes

- Staff uses the same login entry as admin; role controls destination and permissions.
- Password reset starts from `forgot-password.php?mode=staff`.
