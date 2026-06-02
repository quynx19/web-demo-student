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
            <p>Thông tin cá nhân, tài khoản và kết quả học tập.</p>
        </div>
    </section>

    <?php render_flash(); ?>

    <section class="profile-grid">
        <div class="card"><span class="meta-label">Tên đăng nhập</span><strong data-profile-field="username">...</strong></div>
        <div class="card" data-profile-student-only hidden><span class="meta-label">Mã sinh viên</span><strong data-profile-field="student_code">...</strong></div>
        <div class="card"><span class="meta-label">Họ tên</span><strong data-profile-field="full_name">...</strong></div>
        <div class="card"><span class="meta-label">Email</span><strong data-profile-field="email">...</strong></div>
        <div class="card" data-profile-student-only hidden><span class="meta-label">Số điện thoại</span><strong data-profile-field="phone">...</strong></div>
        <div class="card" data-profile-student-only hidden><span class="meta-label">Ngành học</span><strong data-profile-field="major">...</strong></div>
        <div class="card" data-profile-student-only hidden><span class="meta-label">Năm học</span><strong data-profile-field="year">...</strong></div>
        <div class="card"><span class="meta-label">Vai trò</span><strong data-profile-field="role">...</strong></div>
        <div class="card"><span class="meta-label">Trạng thái</span><strong data-profile-field="status">...</strong></div>
    </section>

    <section class="card">
        <h2>Cập nhật hồ sơ</h2>
        <form class="form form-grid" data-profile-form>
            <div class="alert alert-danger" data-form-error hidden></div>
            <div><label for="full_name">Họ tên</label><input class="form-control" id="full_name" name="full_name" type="text"></div>
            <div><label for="email">Email</label><input class="form-control" id="email" name="email" type="email" required></div>
            <div data-profile-student-only hidden><label for="phone">Số điện thoại</label><input class="form-control" id="phone" name="phone" type="text"></div>
            <div class="form-actions theme-form">
                <label><input type="radio" name="theme" value="light" <?= current_theme() === 'light' ? 'checked' : '' ?>> Giao diện sáng</label>
                <label><input type="radio" name="theme" value="dark" <?= current_theme() === 'dark' ? 'checked' : '' ?>> Giao diện tối</label>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Lưu thay đổi</button></div>
        </form>
    </section>

    <section class="card">
        <h2>Đổi mật khẩu</h2>
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

    <section class="card" data-profile-grades-section hidden>
        <h2>Điểm 3 môn học</h2>
        <table class="table grades-table">
            <thead><tr><th>Mã môn</th><th>Môn học</th><th>Điểm</th></tr></thead>
            <tbody data-profile-grades></tbody>
        </table>
    </section>
</div>
<?php render_footer(); ?>
