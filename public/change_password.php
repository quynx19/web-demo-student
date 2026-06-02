<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login();

render_header('Đổi mật khẩu');
?>
<section class="page-header">
    <div><h1>Đổi mật khẩu</h1><p>Mật khẩu được cập nhật qua REST API.</p></div>
</section>
<section class="card">
    <form class="form" data-password-form>
        <div class="alert alert-danger" data-form-error hidden></div>
        <label for="current_password">Mật khẩu hiện tại</label>
        <input class="form-control" id="current_password" name="current_password" type="password" required>
        <label for="new_password">Mật khẩu mới</label>
        <input class="form-control" id="new_password" name="new_password" type="password" required>
        <label for="confirm_password">Nhập lại mật khẩu mới</label>
        <input class="form-control" id="confirm_password" name="confirm_password" type="password" required>
        <button class="btn btn-primary" type="submit">Đổi mật khẩu</button>
    </form>
</section>
<?php render_footer(); ?>
