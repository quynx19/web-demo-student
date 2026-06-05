<?php

declare(strict_types=1);

// Xác thực và phân quyền: quản lý session đăng nhập, đăng xuất và kiểm tra vai trò.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/logger.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

install_app_log_handlers();

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user_role(): string
{
    return (string) ($_SESSION['role'] ?? '');
}

function is_admin(): bool
{
    return current_user_role() === 'admin';
}

function login_user(string $username, string $password): bool
{
    $stmt = get_pdo()->prepare('SELECT id, username, password_hash, role, status FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        write_log('WARNING', 'LOGIN_FAILED', 'Username not found', ['username' => $username]);
        return false;
    }

    if ($user['status'] !== 'active') {
        write_log('WARNING', 'LOGIN_FAILED', 'Account is not active', ['username' => $username, 'status' => $user['status']]);
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        write_log('WARNING', 'LOGIN_FAILED', 'Invalid password', ['username' => $username]);
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    setcookie('last_username', $user['username'], [
        'expires' => time() + 30 * 24 * 60 * 60,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    write_log('INFO', 'LOGIN_SUCCESS', 'Login success');

    return true;
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    write_log('WARNING', 'AUTH_REQUIRED', 'Unauthenticated request blocked');
    redirect('login.php');
}

function require_role(array|string $roles): void
{
    require_login();

    $allowedRoles = is_array($roles) ? $roles : [$roles];
    if (in_array(current_user_role(), $allowedRoles, true)) {
        return;
    }

    write_log('WARNING', 'ACCESS_DENIED', 'Role access denied', [
        'required_roles' => $allowedRoles,
        'current_role' => current_user_role(),
    ]);

    redirect('access_denied.php');
}

function logout_current_user(): void
{
    write_log('INFO', 'LOGOUT', 'User logged out');
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool) ($params['secure'] ?? false),
            (bool) ($params['httponly'] ?? true)
        );
    }

    session_destroy();
}
