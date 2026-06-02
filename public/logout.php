<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

if (is_logged_in()) {
    set_flash('Vui lòng dùng nút đăng xuất để gọi REST API.');
    redirect('index.php');
}

redirect('login.php');
