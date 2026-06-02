<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    $token = $_SESSION['csrf_token'] ?? '';

    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
    }

    return $token;
}

function field_value(string $name, array $source, string $default = ''): string
{
    return trim((string) ($source[$name] ?? $default));
}

function current_theme(): string
{
    return ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
}

function set_flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function get_flash(): ?string
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = (string) $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function render_flash(): void
{
    $flash = get_flash();

    if ($flash === null) {
        return;
    }

    echo '<div class="alert alert-danger">' . e($flash) . '</div>';
}

function role_label(?string $role): string
{
    return $role === 'admin' ? 'Quản trị viên' : 'Người dùng';
}

function render_header(string $title): void
{
    $theme = current_theme();
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $isLoggedIn = isset($_SESSION['user_id']);
    $username = $_SESSION['username'] ?? '';
    $role = $_SESSION['role'] ?? '';

    $navItems = $isLoggedIn ? [
        'index.php' => 'Tổng quan',
        'students.php' => 'Sinh viên',
        'profile.php' => 'Hồ sơ cá nhân',
        'change_password.php' => 'Đổi mật khẩu',
    ] : [
        'login.php' => 'Đăng nhập',
    ];

    if ($role === 'admin') {
        $navItems = array_slice($navItems, 0, 2, true)
            + ['student_add.php' => 'Thêm sinh viên', 'users.php' => 'Quản lý tài khoản', 'logs.php' => 'Nhật ký ứng dụng']
            + array_slice($navItems, 2, null, true);
    }

    echo '<!doctype html>';
    echo '<html lang="vi">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="csrf-token" content="' . e(csrf_token()) . '">';
    echo '<title>' . e($title) . ' - Hồ sơ sinh viên</title>';
    echo '<link rel="stylesheet" href="assets/style.css">';
    echo '<script src="assets/app.js" defer></script>';
    echo '</head>';
    echo '<body class="theme-' . e($theme) . '">';

    if (!$isLoggedIn) {
        echo '<main class="login-main">';
        return;
    }

    echo '<div class="app-layout">';
    echo '<aside class="sidebar">';
    echo '<a class="sidebar-brand" href="index.php"><span>HS</span><strong>Hồ sơ sinh viên</strong></a>';
    echo '<nav class="sidebar-nav">';
    foreach ($navItems as $href => $label) {
        $active = $currentPage === $href ? ' active' : '';
        echo '<a class="sidebar-link' . $active . '" href="' . e($href) . '">' . e($label) . '</a>';
    }
    echo '<form class="sidebar-logout-form" method="post" action="logout.php" data-api-logout>';
    echo '<button class="sidebar-link sidebar-logout" type="submit">Đăng xuất</button>';
    echo '</form>';
    echo '</nav>';
    echo '</aside>';

    echo '<div class="main">';
    echo '<header class="topbar">';
    echo '<div><span class="topbar-label">Xin chào,</span><strong>' . e($username) . '</strong></div>';
    echo '<span class="badge ' . ($role === 'admin' ? 'badge-admin' : 'badge-user') . '">' . e(role_label($role)) . '</span>';
    echo '</header>';
    echo '<main class="content">';
}

function render_footer(): void
{
    echo '</main>';
    if (isset($_SESSION['user_id'])) {
        echo '</div></div>';
    }
    echo '</body>';
    echo '</html>';
}

function generic_error_message(): string
{
    return 'Có lỗi xảy ra, vui lòng thử lại sau.';
}

function render_student_form_fields(array $form, array $errors): void
{
    $fields = [
        'student_code' => ['Mã sinh viên', 'text', true],
        'full_name' => ['Họ tên', 'text', true],
        'email' => ['Email', 'email', true],
        'phone' => ['Số điện thoại', 'text', false],
        'major' => ['Ngành học', 'text', false],
        'year' => ['Năm học', 'number', false],
    ];

    foreach ($fields as $name => [$label, $type, $required]) {
        echo '<div>';
        echo '<label for="' . e($name) . '">' . e($label) . '</label>';
        $requiredAttr = $required ? ' required' : '';
        $extraAttrs = $name === 'year' ? ' min="1" max="6"' : '';
        echo '<input class="form-control" id="' . e($name) . '" name="' . e($name) . '" type="' . e($type) . '" value="' . e($form[$name] ?? '') . '"' . $extraAttrs . $requiredAttr . '>';
        if (isset($errors[$name])) {
            echo '<span class="field-error">' . e($errors[$name]) . '</span>';
        }
        echo '</div>';
    }
}
