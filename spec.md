Company Attendance Management System — Specification

1. Purpose
A role-based attendance system where employees self-register, login to record check-in, and logout to record check-out. HR can view all records, HOC can view department records, and employees can view only their own data. Dashboard provides totals and late entries.

2. Roles
- HR: Full access to users, departments, and all attendance records.
- HOC (Head Of Department): Can view attendance for users within their department.
- Employee: Can view their own attendance and perform login/logout (check-in/out).

3. Core Entities / DB Schema (summary)
- users: id, name, email, password_hash, department_id, role_id, created_at
- departments: id, department_name
- user_roles: id, role_name (e.g., HR, HOC, Employee)
- attendance: id, user_id, attendance_date (DATE), check_in (DATETIME), check_out (DATETIME)

4. Registration & Authentication
- Users self-register via `register.php` (POST name, email, password, department).
- Passwords stored using `password_hash()` (PHP). Unique email enforced.
- Login via `login.php` (POST email, password). On successful login:
  - Start PHP session and store `user_id`, `role_id`, `department_id`.
  - Automatically record check-in: insert attendance row for today when no check_in exists; set `check_in` = current timestamp.
- Logout via `logout.php`: set today's `check_out` to current timestamp and destroy session.

5. Attendance rules
- One attendance row per user per date.
- `attendance_date` uses server date (Y-m-d).
- Late entry detection: configurable cutoff time (default 09:30). Any `check_in` > cutoff flagged as late.

6. Access control
- All API endpoints check session and role.
- `get_attendance.php` returns rows filtered by role: HR -> all, HOC -> department-only, Employee -> own-only.

7. API Endpoints (PHP, JSON responses)
- `backend/register.php` — create user
- `backend/login.php` — authenticate + check-in
- `backend/logout.php` — check-out + logout
- `backend/get_attendance.php` — list attendance (with role filtering)
- `backend/get_dashboard.php` — summary: totals, late counts (with optional date range)
- `backend/get_departments.php` — list departments

8. Frontend pages
- `frontend/register.html` — registration form
- `frontend/index.html` — login form
- `frontend/dashboard.html` — shows stats + links to view attendance
- `frontend/attendance.html` — paginated attendance table + filters

9. UI behavior (jQuery + AJAX)
- Forms submit via AJAX to PHP endpoints.
- On login success, redirect to dashboard; on logout, redirect to login.
- Dashboard requests `get_dashboard.php` and renders totals and late counts.

10. Security
- Use prepared statements (PDO) everywhere.
- Hash passwords with `password_hash()`.
- Protect sensitive pages by checking session and role server-side.
- Deploy over HTTPS in production.

11. Extensibility
- Add CSV export for attendance.
- Add manual corrections for HR role.
- Add mobile-friendly UI.

12. Validation
- Frontend: basic required-field checks and email format.
- Backend: re-validate all inputs; enforce unique email.

13. Deploy notes
- PHP 7.4+ recommended with PDO MySQL.
- MySQL 5.7+ (or MariaDB) recommended.
