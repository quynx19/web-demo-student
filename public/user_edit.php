<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('Tài khoản không hợp lệ.');
    redirect('users.php');
}

render_header('Sửa tài khoản');
?>
<section class="page-header"><div><h1>Sửa tài khoản</h1><p>Cập nhật tài khoản qua REST API.</p></div></section>
<section class="card">
    <form class="form form-grid" data-user-form data-user-id="<?= e($id) ?>">
        <div class="alert alert-danger" data-form-error hidden></div>
        <div><label>Tên đăng nhập</label><input class="form-control" name="username" disabled></div>
        <div><label>Họ tên</label><input class="form-control" name="full_name"></div>
        <div><label>Email</label><input class="form-control" name="email" type="email" required></div>
        <div><label>Vai trò</label><select class="form-select" name="role"><option value="user">Người dùng</option><option value="admin">Quản trị viên</option></select></div>
        <div><label>Trạng thái</label><select class="form-select" name="status"><option value="active">Đang hoạt động</option><option value="locked">Đã khóa</option></select></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Cập nhật</button><a class="btn btn-secondary" href="users.php">Hủy</a></div>
    </form>
</section>
<?php render_footer(); ?>
