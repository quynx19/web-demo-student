<?php

declare(strict_types=1);

// Quản lý tài khoản: CRUD user, liên kết sinh viên và đổi/cập nhật thông tin tài khoản.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

function list_users(): array
{
    $stmt = get_pdo()->query(
        'SELECT users.id, users.student_id, users.username, users.full_name, users.email,
                users.role, users.status, users.created_at, users.updated_at,
                students.student_code, students.full_name AS student_name
         FROM users
         LEFT JOIN students ON students.id = users.student_id
         ORDER BY users.id ASC'
    );
    return $stmt->fetchAll();
}

function count_users(): int
{
    return (int) get_pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

function get_user_by_id(int $id): ?array
{
    $stmt = get_pdo()->prepare('SELECT id, student_id, username, password_hash, full_name, email, role, status, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function username_exists(string $username, ?int $excludeId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM users WHERE username = :username';
    $params = ['username' => $username];

    if ($excludeId !== null) {
        $sql .= ' AND id <> :id';
        $params['id'] = $excludeId;
    }

    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function user_student_id(array $data): ?int
{
    $studentId = $data['student_id'] ?? null;

    if ($studentId === null || $studentId === '') {
        return null;
    }

    if (!is_scalar($studentId) || !ctype_digit((string) $studentId) || (int) $studentId <= 0) {
        return -1;
    }

    return (int) $studentId;
}

function user_student_exists(int $studentId): bool
{
    $stmt = get_pdo()->prepare('SELECT COUNT(*) FROM students WHERE id = :id');
    $stmt->execute(['id' => $studentId]);

    return (int) $stmt->fetchColumn() > 0;
}

function student_account_exists(int $studentId, ?int $excludeId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM users WHERE student_id = :student_id';
    $params = ['student_id' => $studentId];

    if ($excludeId !== null) {
        $sql .= ' AND id <> :id';
        $params['id'] = $excludeId;
    }

    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function validate_user_data(array $data, bool $requirePassword = false, ?int $excludeId = null): array
{
    $errors = [];
    $username = field_value('username', $data);
    $email = field_value('email', $data);
    $role = field_value('role', $data, 'user');
    $status = field_value('status', $data, 'active');
    $studentId = user_student_id($data);

    if ($role === 'admin') {
        $studentId = null;
    }

    if ($username === '') {
        $errors['username'] = 'Tên đăng nhập không được để trống.';
    } elseif (username_exists($username, $excludeId)) {
        $errors['username'] = 'Tên đăng nhập đã tồn tại.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không đúng định dạng.';
    }

    if (!in_array($role, ['admin', 'user'], true)) {
        $errors['role'] = 'Vai trò không hợp lệ.';
    }

    if (!in_array($status, ['active', 'locked'], true)) {
        $errors['status'] = 'Trạng thái không hợp lệ.';
    }

    if ($requirePassword && strlen((string) ($data['password'] ?? '')) < 6) {
        $errors['password'] = 'Mật khẩu phải tối thiểu 6 ký tự.';
    }

    if ($studentId === -1 || ($studentId !== null && !user_student_exists($studentId))) {
        $errors['student_id'] = 'Sinh viên liên kết không hợp lệ.';
    } elseif ($role === 'user' && $studentId === null) {
        $errors['student_id'] = 'Tài khoản sinh viên phải được liên kết với một sinh viên.';
    } elseif ($studentId !== null && student_account_exists($studentId, $excludeId)) {
        $errors['student_id'] = 'Sinh viên này đã có tài khoản.';
    }

    return $errors;
}

function normalize_student_account_data(array $data): array
{
    return [
        'account_username' => field_value('account_username', $data),
        'account_password' => (string) ($data['account_password'] ?? ''),
        'account_status' => field_value('account_status', $data, 'active'),
    ];
}

function validate_student_account_data(array $data): array
{
    $errors = [];
    $account = normalize_student_account_data($data);

    if ($account['account_username'] === '') {
        $errors['account_username'] = 'Tên đăng nhập không được để trống.';
    } elseif (username_exists($account['account_username'])) {
        $errors['account_username'] = 'Tên đăng nhập đã tồn tại.';
    }

    if (strlen($account['account_password']) < 6) {
        $errors['account_password'] = 'Mật khẩu ban đầu phải tối thiểu 6 ký tự.';
    }

    if (!in_array($account['account_status'], ['active', 'locked'], true)) {
        $errors['account_status'] = 'Trạng thái tài khoản không hợp lệ.';
    }

    return $errors;
}

function create_user(array $data): int
{
    $studentId = field_value('role', $data, 'user') === 'user' ? user_student_id($data) : null;
    $stmt = get_pdo()->prepare(
        'INSERT INTO users (student_id, username, password_hash, full_name, email, role, status)
         VALUES (:student_id, :username, :password_hash, :full_name, :email, :role, :status)'
    );
    $stmt->execute([
        'student_id' => $studentId,
        'username' => field_value('username', $data),
        'password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
        'full_name' => field_value('full_name', $data),
        'email' => field_value('email', $data),
        'role' => field_value('role', $data, 'user'),
        'status' => field_value('status', $data, 'active'),
    ]);

    return (int) get_pdo()->lastInsertId();
}

function update_user(int $id, array $data): void
{
    $studentId = field_value('role', $data, 'user') === 'user' ? user_student_id($data) : null;
    $stmt = get_pdo()->prepare(
        'UPDATE users
         SET student_id = :student_id, full_name = :full_name, email = :email, role = :role, status = :status
         WHERE id = :id'
    );
    $stmt->execute([
        'id' => $id,
        'student_id' => $studentId,
        'full_name' => field_value('full_name', $data),
        'email' => field_value('email', $data),
        'role' => field_value('role', $data, 'user'),
        'status' => field_value('status', $data, 'active'),
    ]);
}

function delete_user(int $id): bool
{
    $stmt = get_pdo()->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}

function update_current_user_profile(int $id, array $data): void
{
    $stmt = get_pdo()->prepare('UPDATE users SET full_name = :full_name, email = :email WHERE id = :id');
    $stmt->execute([
        'id' => $id,
        'full_name' => field_value('full_name', $data),
        'email' => field_value('email', $data),
    ]);
}

function update_linked_student_account(int $studentId, array $student): void
{
    $stmt = get_pdo()->prepare('UPDATE users SET full_name = :full_name, email = :email WHERE student_id = :student_id AND role = :role');
    $stmt->execute([
        'student_id' => $studentId,
        'full_name' => field_value('full_name', $student),
        'email' => field_value('email', $student),
        'role' => 'user',
    ]);
}

function change_user_password(int $id, string $newPassword): void
{
    $stmt = get_pdo()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
    $stmt->execute([
        'id' => $id,
        'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
    ]);
}
