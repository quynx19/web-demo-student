<?php

declare(strict_types=1);

// Đăng xuất: kết thúc session đăng nhập hiện tại.
require_once __DIR__ . '/../app/auth.php';

if (is_logged_in() && is_post()) {
    logout_current_user();
}

redirect('login.php');
