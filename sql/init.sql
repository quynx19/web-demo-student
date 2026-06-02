CREATE DATABASE IF NOT EXISTS web_demo_student
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE web_demo_student;

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

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) NOT NULL DEFAULT '',
    email VARCHAR(120) NOT NULL DEFAULT '',
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'locked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_student_id (student_id),
    CONSTRAINT fk_users_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Keep this file safe to run against databases created by older project versions.
ALTER TABLE users ADD COLUMN IF NOT EXISTS student_id INT UNSIGNED NULL AFTER id;
ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(120) NOT NULL DEFAULT '' AFTER password_hash;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(120) NOT NULL DEFAULT '' AFTER full_name;
ALTER TABLE users MODIFY role ENUM('admin', 'user') NOT NULL DEFAULT 'user';
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'locked') NOT NULL DEFAULT 'active' AFTER role;

SET @users_student_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND index_name = 'uq_users_student_id'
);
SET @users_student_index_sql = IF(
    @users_student_index_exists = 0,
    'ALTER TABLE users ADD UNIQUE INDEX uq_users_student_id (student_id)',
    'SELECT 1'
);
PREPARE users_student_index_stmt FROM @users_student_index_sql;
EXECUTE users_student_index_stmt;
DEALLOCATE PREPARE users_student_index_stmt;

SET @users_student_fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND table_name = 'users'
      AND constraint_name = 'fk_users_student'
);
SET @users_student_fk_sql = IF(
    @users_student_fk_exists = 0,
    'ALTER TABLE users ADD CONSTRAINT fk_users_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE users_student_fk_stmt FROM @users_student_fk_sql;
EXECUTE users_student_fk_stmt;
DEALLOCATE PREPARE users_student_fk_stmt;

CREATE TABLE IF NOT EXISTS student_grades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    subject_code VARCHAR(30) NOT NULL,
    subject_name VARCHAR(120) NOT NULL,
    score DECIMAL(4, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_grades_subject (student_id, subject_code),
    CONSTRAINT fk_student_grades_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

DELETE FROM users WHERE username = 'user';

INSERT INTO users (student_id, username, password_hash, full_name, email, role, status)
VALUES
    (NULL, 'admin', '$2y$10$/x6W8x3LBT/1EbIOwgh1x.tBriAqDc4vCJYmcCLeSVZ.nquMMBGkC', 'Quản trị viên', 'admin@example.com', 'admin', 'active'),
    ((SELECT id FROM students WHERE student_code = 'SV001'), 'user01', '$2y$10$ojLzmr3lLP7HWAYEUCw.Q.wlJ0eKb0B0tI2gvK5NK/Gyqq0F3XV.C', 'Nguyễn Văn An', 'an.nguyen@example.com', 'user', 'active')
ON DUPLICATE KEY UPDATE
    student_id = VALUES(student_id),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    email = VALUES(email),
    role = VALUES(role),
    status = VALUES(status);

INSERT INTO student_grades (student_id, subject_code, subject_name, score)
SELECT students.id, subjects.subject_code, subjects.subject_name,
       CASE students.student_code
           WHEN 'SV001' THEN CASE subjects.subject_code WHEN 'MATH' THEN 7.20 WHEN 'PROGRAMMING' THEN 7.70 ELSE 7.00 END
           WHEN 'SV002' THEN CASE subjects.subject_code WHEN 'MATH' THEN 7.40 WHEN 'PROGRAMMING' THEN 7.90 ELSE 7.20 END
           WHEN 'SV003' THEN CASE subjects.subject_code WHEN 'MATH' THEN 7.60 WHEN 'PROGRAMMING' THEN 8.10 ELSE 7.40 END
           WHEN 'SV004' THEN CASE subjects.subject_code WHEN 'MATH' THEN 7.80 WHEN 'PROGRAMMING' THEN 8.30 ELSE 7.60 END
           ELSE CASE subjects.subject_code WHEN 'MATH' THEN 8.00 WHEN 'PROGRAMMING' THEN 8.50 ELSE 7.80 END
       END
FROM students
CROSS JOIN (
    SELECT 'MATH' AS subject_code, 'Toán' AS subject_name
    UNION ALL SELECT 'PROGRAMMING', 'Lập trình'
    UNION ALL SELECT 'DATABASE', 'Cơ sở dữ liệu'
) AS subjects
WHERE students.student_code IN ('SV001', 'SV002', 'SV003', 'SV004', 'SV005')
ON DUPLICATE KEY UPDATE
    subject_name = VALUES(subject_name),
    score = VALUES(score);
