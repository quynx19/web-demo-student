<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

if (is_logged_in() && is_post() && valid_csrf_token()) {
    logout_current_user();
}

redirect('login.php');
