CREATE DATABASE IF NOT EXISTS web_demo_student
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE web_demo_student;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) NOT NULL DEFAULT '',
    email VARCHAR(120) NOT NULL DEFAULT '',
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'locked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(120) NOT NULL DEFAULT '' AFTER password_hash;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(120) NOT NULL DEFAULT '' AFTER full_name;
ALTER TABLE users MODIFY role ENUM('admin', 'user') NOT NULL DEFAULT 'user';
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'locked') NOT NULL DEFAULT 'active' AFTER role;

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NULL,
    major VARCHAR(120) NULL,
    year TINYINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_students_search (student_code, full_name, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM users WHERE username = 'user';

INSERT INTO users (username, password_hash, full_name, email, role, status)
VALUES
    ('admin', '$2y$10$/x6W8x3LBT/1EbIOwgh1x.tBriAqDc4vCJYmcCLeSVZ.nquMMBGkC', 'Quản trị viên', 'admin@example.com', 'admin', 'active'),
    ('user01', '$2y$10$ojLzmr3lLP7HWAYEUCw.Q.wlJ0eKb0B0tI2gvK5NK/Gyqq0F3XV.C', 'Người dùng thường', 'user01@example.com', 'user', 'active')
ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    email = VALUES(email),
    role = VALUES(role),
    status = VALUES(status);

INSERT INTO students (student_code, full_name, email, phone, major, year)
VALUES
    ('SV001', 'Nguyễn Văn An', 'an.nguyen@example.com', '0901000001', 'Công nghệ thông tin', 3),
    ('SV002', 'Trần Thị Bình', 'binh.tran@example.com', '0901000002', 'Hệ thống thông tin', 2),
    ('SV003', 'Lê Minh Châu', 'chau.le@example.com', '0901000003', 'Khoa học dữ liệu', 4),
    ('SV004', 'Phạm Quốc Dũng', 'dung.pham@example.com', '0901000004', 'An toàn thông tin', 1),
    ('SV005', 'Hoàng Mai Linh', 'linh.hoang@example.com', '0901000005', 'Kỹ thuật phần mềm', 3)
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    email = VALUES(email),
    phone = VALUES(phone),
    major = VALUES(major),
    year = VALUES(year);
