<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login();

render_header('Hồ sơ cá nhân');
?>
<div data-profile-page>
    <section class="page-header">
        <div>
            <h1>Hồ sơ cá nhân</h1>
            <p>Dữ liệu hồ sơ được tải và cập nhật qua REST API.</p>
        </div>
        <a class="btn btn-primary" href="change_password.php">Đổi mật khẩu</a>
    </section>

    <?php render_flash(); ?>

    <section class="profile-grid">
        <div class="card"><span class="meta-label">Tên đăng nhập</span><strong data-profile-field="username">...</strong></div>
        <div class="card"><span class="meta-label">Họ tên</span><strong data-profile-field="full_name">...</strong></div>
        <div class="card"><span class="meta-label">Email</span><strong data-profile-field="email">...</strong></div>
        <div class="card"><span class="meta-label">Vai trò</span><strong data-profile-field="role">...</strong></div>
        <div class="card"><span class="meta-label">Trạng thái</span><strong data-profile-field="status">...</strong></div>
    </section>

    <section class="card">
        <h2>Cập nhật hồ sơ</h2>
        <form class="form form-grid" data-profile-form>
            <div class="alert alert-danger" data-form-error hidden></div>
            <div><label for="full_name">Họ tên</label><input class="form-control" id="full_name" name="full_name" type="text"></div>
            <div><label for="email">Email</label><input class="form-control" id="email" name="email" type="email" required></div>
            <div class="form-actions theme-form">
                <label><input type="radio" name="theme" value="light" <?= current_theme() === 'light' ? 'checked' : '' ?>> Giao diện sáng</label>
                <label><input type="radio" name="theme" value="dark" <?= current_theme() === 'dark' ? 'checked' : '' ?>> Giao diện tối</label>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Lưu thay đổi</button></div>
        </form>
    </section>
</div>
<?php render_footer(); ?>
