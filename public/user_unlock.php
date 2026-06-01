<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/user.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);

try {
    set_user_status($id, 'active');
    write_log('INFO', 'USER_UNLOCKED', 'User unlocked', ['target_user_id' => $id]);
    set_flash('success', 'Đã mở khóa tài khoản.');
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'Failed to unlock user', ['error' => $exception->getMessage(), 'target_user_id' => $id]);
    set_flash('error', generic_error_message());
}

redirect('users.php');
