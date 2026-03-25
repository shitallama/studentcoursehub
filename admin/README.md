# Admin Guide

This guide covers admin-side pages and actions.

## Access

- Login first at: `http://localhost/studentcoursehub-main/staff/controller/staff-login.php`
- Admin dashboard: `http://localhost/studentcoursehub-main/admin/view/dashboard.php`

## Main Admin Pages

- Programmes: `http://localhost/studentcoursehub-main/admin/view/programmes.php`
- Modules: `http://localhost/studentcoursehub-main/admin/view/modules.php`
- Staff: `http://localhost/studentcoursehub-main/admin/view/staff.php`
- Students: `http://localhost/studentcoursehub-main/admin/view/students.php`
- Manage Users: `http://localhost/studentcoursehub-main/admin/view/manage-users.php`

## Folder Responsibilities

- `admin/view`: Admin UI pages.
- `admin/controller`: Admin create/edit/delete flows.
- `admin/models`: Admin data-layer files (if used).

## Notes

- Some pages combine PHP logic and HTML markup.
- Route protection is enforced through `includes/auth_check.php`.
