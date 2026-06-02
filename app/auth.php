<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/logger.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

function current_student_id(): ?int
{
    $studentId = $_SESSION['student_id'] ?? null;

    return is_int($studentId) && $studentId > 0 ? $studentId : null;
}

function login_user(string $username, string $password): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id, student_id, username, password_hash, full_name, email, role, status FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && $user['status'] === 'locked') {
        write_log('WARNING', 'LOGIN_FAILED', 'Locked account login attempt', ['username' => $username]);
        return false;
    }

    if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['student_id'] = $user['student_id'] === null ? null : (int) $user['student_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['status'] = $user['status'];

        setcookie('last_username', $user['username'], [
            'expires' => time() + 30 * 24 * 60 * 60,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        write_log('INFO', 'LOGIN_SUCCESS', 'Login success', ['user_id' => (int) $user['id']]);
        return true;
    }

    write_log('WARNING', 'LOGIN_FAILED', 'Invalid login attempt', ['username' => $username]);
    return false;
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    write_log('WARNING', 'PAGE_ACCESS', 'Unauthenticated access blocked', [
        'target' => $_SERVER['REQUEST_URI'] ?? '',
    ]);
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
        'target' => $_SERVER['REQUEST_URI'] ?? '',
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
