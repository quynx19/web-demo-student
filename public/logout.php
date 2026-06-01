<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

if (is_logged_in()) {
    logout_current_user();
}

redirect('login.php');
