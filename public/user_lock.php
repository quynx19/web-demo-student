<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/user.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
if ($id === (int) $_SESSION['user_id']) {
    set_flash('error', 'Không thể tự khóa tài khoản đang đăng nhập.');
    redirect('users.php');
}

try {
    set_user_status($id, 'locked');
    write_log('INFO', 'USER_LOCKED', 'User locked', ['target_user_id' => $id]);
    set_flash('success', 'Đã khóa tài khoản.');
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'Failed to lock user', ['error' => $exception->getMessage(), 'target_user_id' => $id]);
    set_flash('error', generic_error_message());
}

redirect('users.php');
