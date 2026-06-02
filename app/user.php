<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

function list_users(): array
{
    $stmt = get_pdo()->query('SELECT id, username, full_name, email, role, status, created_at, updated_at FROM users ORDER BY id ASC');
    return $stmt->fetchAll();
}

function user_create_fields(): array
{
    return ['username', 'password', 'full_name', 'email', 'role', 'status'];
}

function user_update_fields(): array
{
    return ['full_name', 'email', 'role', 'status'];
}

function count_users(): int
{
    return (int) get_pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

function get_user_by_id(int $id): ?array
{
    $stmt = get_pdo()->prepare('SELECT id, username, password_hash, full_name, email, role, status, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
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

function validate_user_data(array $data, bool $requirePassword = false, ?int $excludeId = null): array
{
    $errors = [];
    $username = field_value('username', $data);
    $email = field_value('email', $data);
    $role = field_value('role', $data, 'user');
    $status = field_value('status', $data, 'active');

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

    return $errors;
}

function create_user(array $data): int
{
    $stmt = get_pdo()->prepare(
        'INSERT INTO users (username, password_hash, full_name, email, role, status)
         VALUES (:username, :password_hash, :full_name, :email, :role, :status)'
    );
    $stmt->execute([
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
    $stmt = get_pdo()->prepare(
        'UPDATE users
         SET full_name = :full_name, email = :email, role = :role, status = :status
         WHERE id = :id'
    );
    $stmt->execute([
        'id' => $id,
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

function change_user_password(int $id, string $newPassword): void
{
    $stmt = get_pdo()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
    $stmt->execute([
        'id' => $id,
        'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
    ]);
}
