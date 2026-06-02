-- Run this once on an existing attendance_system database to link attendance
-- records to users by employee_code instead of users.id.
USE attendance_system;

-- This requires every user to have a unique employee_code.
ALTER TABLE users
  ADD UNIQUE KEY employee_code_unique (employee_code);

-- Backfill employee_code on old attendance rows before removing user_id.
UPDATE attendance a
JOIN users u ON u.id = a.user_id
SET a.employee_code = u.employee_code
WHERE a.employee_code IS NULL OR a.employee_code = '';

ALTER TABLE attendance
  DROP FOREIGN KEY attendance_ibfk_1;

ALTER TABLE attendance
  DROP INDEX user_date_unique,
  DROP COLUMN user_id,
  ADD UNIQUE KEY employee_date_unique (employee_code, attendance_date),
  ADD CONSTRAINT attendance_employee_code_fk
    FOREIGN KEY (employee_code) REFERENCES users(employee_code)
    ON DELETE CASCADE
    ON UPDATE CASCADE;
