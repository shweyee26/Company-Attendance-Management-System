Company Attendance Management System

Overview
- Frontend: HTML, CSS, jQuery
- Backend: PHP (PDO)
- Database: MySQL, database name: `attendance_system`

What this scaffold includes
- `spec.md` — detailed functional + technical spec
- `sql/schema.sql` — SQL to create database and tables
- `frontend/` — HTML/CSS/JS placeholders (login, register, dashboard)
- `backend/` — PHP endpoints (config, register, login, logout, attendance API)

Quick setup
1. Create a MySQL database named `attendance_system` and run `sql/schema.sql`.
2. Place the project in your web server root (e.g., XAMPP `htdocs`).
3. Update `backend/config.php` with DB credentials.
4. Open `frontend/index.html` to start.

Next steps
- Customize UI and add validation.
- Harden security (HTTPS, CSRF tokens).
- Add unit/integration tests for API endpoints.
