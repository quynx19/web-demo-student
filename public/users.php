<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_role('admin');

render_header('Quản lý tài khoản');
?>
<section class="page-header">
    <div>
        <h1>Quản lý tài khoản</h1>
        <p>Quản lý tài khoản và sinh viên được liên kết.</p>
    </div>
    <a class="btn btn-primary" href="user_form.php">Thêm tài khoản</a>
</section>

<?php render_flash(); ?>

<section class="table-card" data-users-list data-current-user-id="<?= e($_SESSION['user_id']) ?>">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Sinh viên liên kết</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody><tr><td colspan="8" class="empty-state">Đang tải dữ liệu...</td></tr></tbody>
    </table>
</section>
<?php render_footer(); ?>
