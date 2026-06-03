<?php

declare(strict_types=1);

// Đăng xuất: chỉ chấp nhận POST hợp lệ để kết thúc session.
require_once __DIR__ . '/../app/auth.php';

if (is_logged_in() && is_post() && valid_csrf_token()) {
    logout_current_user();
}

redirect('login.php');
