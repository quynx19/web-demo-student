<?php

declare(strict_types=1);

// Trang đăng nhập: xác thực tài khoản và chuyển hướng theo vai trò.
require_once __DIR__ . '/../app/auth.php';

if (is_logged_in()) {
    redirect(landing_page());
}

$error = '';
if (is_post()) {
    if (!login_user(field_value('username', $_POST), (string) ($_POST['password'] ?? ''))) {
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    } else {
        redirect(landing_page());
    }
}

render_header('Đăng nhập hệ thống');
?>
<section class="login-card">
    <h1>Đăng nhập hệ thống</h1>
    <p>Hệ thống quản lý hồ sơ sinh viên</p>
    <?php render_flash(); ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form">
        <div><label for="username">Tên đăng nhập</label><input class="form-control" id="username" name="username" type="text" autocomplete="username" required></div>
        <div><label for="password">Mật khẩu</label><input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="btn btn-primary button-full" type="submit">Đăng nhập</button>
    </form>
    <div class="login-footer">Hệ thống quản lý hồ sơ sinh viên</div>
</section>
<?php render_footer(); ?>
