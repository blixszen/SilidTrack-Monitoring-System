-- SilidTrack Database Schema
-- Smart Classroom & Attendance System
-- Import this via phpMyAdmin or: mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS silidtrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE silidtrack;

-- ==========================================================
-- ADMIN ACCOUNTS
-- ==========================================================
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) DEFAULT 'Admin',
    photo_path VARCHAR(255) DEFAULT NULL,
    maya_account_name VARCHAR(150) DEFAULT NULL,     -- Maya bank account name shown to students
    maya_account_number VARCHAR(50) DEFAULT NULL,     -- Maya bank account number
    maya_qr_path VARCHAR(255) DEFAULT NULL,          -- uploaded QR code image
    paymongo_secret_key VARCHAR(255) DEFAULT NULL,
    paymongo_public_key VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed default admin (password: admin123 -- change immediately)
-- Hash generated with PHP password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO admins (email, password_hash, full_name) VALUES
('admin@silidtrack.edu', '$2y$10$92IX0NpkS0ktNlPGw1P8/eb6.PjOEqzOR/A.WMvzMIB3IqAeYq0va', 'Admin');
-- NOTE: the hash above is a placeholder pattern; run reset_admin_password.php (included)
-- after first install to set a real hash for 'admin123'.

-- ==========================================================
-- STUDENTS
-- ==========================================================
CREATE TABLE students (
    student_pk INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(30) NOT NULL UNIQUE,      -- e.g. 2024-101-1
    full_name VARCHAR(150) NOT NULL,
    program VARCHAR(100) DEFAULT NULL,
    section VARCHAR(50) DEFAULT NULL,
    rfid_uid VARCHAR(50) DEFAULT NULL UNIQUE,     -- UID from RC522
    password_hash VARCHAR(255) NOT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ==========================================================
-- ATTENDANCE
-- ==========================================================
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_pk INT NOT NULL,
    scan_date DATE NOT NULL,
    scan_time TIME DEFAULT NULL,
    status ENUM('Present','Late','Absent','Excused') NOT NULL DEFAULT 'Absent',
    id_detected ENUM('Yes','No') NOT NULL DEFAULT 'No',
    is_override TINYINT(1) DEFAULT 0,             -- 1 = manually set by admin
    overridden_by INT DEFAULT NULL,               -- admin_id
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_pk) REFERENCES students(student_pk) ON DELETE CASCADE,
    UNIQUE KEY unique_scan_per_day (student_pk, scan_date)
) ENGINE=InnoDB;

-- ==========================================================
-- ANNOUNCEMENTS
-- ==========================================================
CREATE TABLE announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    fee_id INT DEFAULT NULL,                      -- optional link to a fee
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ==========================================================
-- FEES
-- ==========================================================
CREATE TABLE fees (
    fee_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    created_by INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ==========================================================
-- PAYMENTS (PayMongo)
-- ==========================================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) NOT NULL UNIQUE,   -- e.g. PM-F2615F11
    student_pk INT NOT NULL,
    fee_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending','Paid','Failed') NOT NULL DEFAULT 'Pending',
    paid_at DATETIME DEFAULT NULL,
    paymongo_session_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_pk) REFERENCES students(student_pk) ON DELETE CASCADE,
    FOREIGN KEY (fee_id) REFERENCES fees(fee_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================================
-- APP SETTINGS (Maya bank account, QR photo path, etc.)
-- Also created on demand by includes/settings.php
-- ==========================================================
CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- USEFUL VIEWS
-- ==========================================================

-- Today's attendance summary (used by dashboard stat cards)
CREATE OR REPLACE VIEW v_today_attendance AS
SELECT
    (SELECT COUNT(*) FROM students WHERE is_active = 1) AS total_students,
    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) AS late_count,
    SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS absent_count
FROM attendance a
WHERE a.scan_date = CURDATE();

-- At-risk students (3+ absences in last 30 days)
CREATE OR REPLACE VIEW v_at_risk AS
SELECT s.student_id, s.full_name, s.student_pk,
       COUNT(*) AS total_absences
FROM attendance a
JOIN students s ON s.student_pk = a.student_pk
WHERE a.status = 'Absent'
  AND a.scan_date >= (CURDATE() - INTERVAL 30 DAY)
GROUP BY s.student_pk
ORDER BY total_absences DESC;

-- Unpaid balances
CREATE OR REPLACE VIEW v_unpaid_balances AS
SELECT s.student_id, s.full_name, s.student_pk, f.fee_id, f.title AS fee_title,
       f.amount, f.due_date
FROM fees f
CROSS JOIN students s
WHERE f.is_active = 1
  AND s.is_active = 1
  AND NOT EXISTS (
      SELECT 1 FROM payments p
      WHERE p.fee_id = f.fee_id
        AND p.student_pk = s.student_pk
        AND p.status = 'Paid'
  );

-- ==========================================================
-- MIGRATIONS FOR EXISTING INSTALLATIONS
-- Run these once if the database was imported before the
-- 'Excused' status and the settings store were added.
-- ==========================================================
-- ALTER TABLE attendance
--   MODIFY status ENUM('Present','Late','Absent','Excused') NOT NULL DEFAULT 'Absent';

-- CREATE TABLE IF NOT EXISTS app_settings (
--     setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
--     setting_value TEXT DEFAULT NULL,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
