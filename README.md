# Student Course Hub

Student Course Hub is a PHP + MySQL web app for browsing programmes, registering student interest, and managing programmes/modules through role-based dashboards.

The project now uses a split route structure:

- `student/controller` and `student/view`
- `staff/controller` and `staff/view`
- `admin/controller` and `admin/view`

## Architecture Note

This project does **not** currently enforce a strict separation of backend and frontend code.

- Several pages intentionally contain both PHP logic and HTML markup in the same file.
- The folder split (`view` / `controller` / `models`) is used as a guide, but the codebase is still partially mixed in places.

## Tech Stack

- PHP (PDO)
- MySQL
- XAMPP (Apache + MySQL)
- HTML/CSS/JS

## Project Location (XAMPP)

Place the project folder inside:

`C:\xampp\htdocs\studentcoursehub-main`

## Quick Start (Windows + XAMPP)

1. Start Apache and MySQL in XAMPP Control Panel.
2. Import database schema/data from `database/database.sql` using phpMyAdmin.
3. Confirm database connection in `includes/db.php`:
	 - Host: `localhost`
	 - Port: `3306`
	 - Database: `student_course_hub`
	 - User: `root`
	 - Password: empty (XAMPP default)
4. Open the app at:
	 - `http://localhost/studentcoursehub-main/`

## Authentication and Roles

- Student:
	- Sign up and login from student auth pages.
	- Can register/unregister interest in programmes.
	- Has a student dashboard and profile page.
- Staff:
	- Logs in via `staff/controller/staff-login.php`.
	- Can manage modules assigned to them.
	- Staff self-signup is closed.
- Admin:
	- Logs in via `staff/controller/staff-login.php`.
	- Can manage users, staff, programmes, modules, and interested students.

## Key URLs

- Home:
	- `http://localhost/studentcoursehub-main/`
- Programmes listing:
	- `http://localhost/studentcoursehub-main/home.php`
- Student login:
	- `http://localhost/studentcoursehub-main/student/controller/student-login.php`
- Student signup:
	- `http://localhost/studentcoursehub-main/student/controller/student-signup.php`
- Staff/Admin login:
	- `http://localhost/studentcoursehub-main/staff/controller/staff-login.php`
- Forgot password:
	- `http://localhost/studentcoursehub-main/forgot-password.php`

## Seed Admin Login

After importing `database/database.sql`, use:

- Login URL: `http://localhost/studentcoursehub-main/staff/controller/staff-login.php`
- Username: `SuperAdmin`
- Or email: `admin@edu.nielsbrock.dk`
- Password: `Superadmin123.`

## Security Features

- University email validation for restricted account flows (`@edu.nielsbrock.dk`).
- Role-based route protection in `includes/auth_check.php`.
- Password reset flow (`forgot-password.php`, `reset-password.php`).
- DB-backed auth rate limiting via `AuthRateLimits`.
- Audit logging for privileged actions.

## Data Notes

- `Users.FullName` is supported and used for display where available.
- Existing records are backfilled by SQL migration logic in `database/database.sql`.

## UI Notes

- Cookie consent banner is included (`includes/cookie_consent.php`).
- Visible pages include browser titles in the format:
	- `<title>Page Name | Student Course Hub</title>`

## Important Folders

- `admin/view`: admin and staff management pages.
- `admin/controller`: create/edit/delete actions for admin/staff tools.
- `student/view`: student dashboard, profile, programme details.
- `student/controller`: student login/signup/unregister actions.
- `staff/view`: staff dashboard.
- `staff/controller`: staff/admin login flow.

## Further Guides

For role-specific walkthroughs and access points, also check:

- `admin/README.md`
- `student/README.md`
- `staff/README.md`
