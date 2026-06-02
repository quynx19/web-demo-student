<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

render_header('Đăng nhập hệ thống');
?>
<section class="login-card">
    <h1>Đăng nhập hệ thống</h1>
    <p>Hệ thống quản lý hồ sơ sinh viên</p>

    <?php render_flash(); ?>

    <form method="post" class="form" data-login-form>
        <div class="alert alert-danger" data-form-error hidden></div>
        <div>
            <label for="username">Tên đăng nhập</label>
            <input class="form-control" id="username" name="username" type="text" autocomplete="username" required>
        </div>
        <div>
            <label for="password">Mật khẩu</label>
            <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary button-full" type="submit">Đăng nhập</button>
    </form>
    <div class="login-footer">Demo REST API Application</div>
</section>
<?php render_footer(); ?>
