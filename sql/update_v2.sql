USE web_demo_student;

ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(120) NOT NULL DEFAULT '' AFTER password_hash;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(120) NOT NULL DEFAULT '' AFTER full_name;
ALTER TABLE users MODIFY role ENUM('admin', 'user') NOT NULL DEFAULT 'user';
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'locked') NOT NULL DEFAULT 'active' AFTER role;

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
