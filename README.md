# studentcoursehub

Run this project with XAMPP (Apache + MySQL).

## Requirements

- XAMPP (Apache + MySQL)

## Run With XAMPP (Windows)

1. Place this project in your XAMPP web root:

	C:\\xampp\\htdocs\\studentcoursehub-xampp

2. Start Apache and MySQL from XAMPP Control Panel.

3. Create and seed the database:

	- Open phpMyAdmin: http://localhost/phpmyadmin
	- Go to Import and select:

	  database/database.sql

	- Click Go.

4. Confirm database connection settings in includes/db.php:

	- Host: localhost
	- Port: 3306
	- Database: student_course_hub
	- User: root
	- Password: (empty by default in XAMPP)

	The app already uses these as fallback defaults when Docker environment variables are not present.

5. Open the app:

	http://localhost/studentcoursehub-xampp/ 
	http://localhost/studentcoursehub-main/ (if downloaded from GitHub, it renames the folder as studentcoursehub-main)

## Optional: Admin login seed

The SQL seed includes an admin row with a placeholder hashed password:

`$2y$10$YourHashedPasswordHere`

Replace it with a real hash (or create an admin account through your app flow) before using admin login.

## Cookie consent

The site now shows a cookie consent banner.

- Essential cookies are used for sessions/login.
- Users can accept or reject optional cookies.
- Consent choice is stored as the `cookie_consent` cookie.
