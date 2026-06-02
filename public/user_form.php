<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_role('admin');

$rawId = $_GET['id'] ?? '';
$id = (int) $rawId;
if ($rawId !== '' && $id <= 0) {
    set_flash('Tài khoản không hợp lệ.');
    redirect('users.php');
}

$isEdit = $id > 0;

render_header($isEdit ? 'Sửa tài khoản' : 'Thêm tài khoản');
?>
<section class="page-header">
    <div>
        <h1><?= $isEdit ? 'Sửa tài khoản' : 'Thêm tài khoản' ?></h1>
        <p><?= $isEdit ? 'Chỉnh sửa thông tin và trạng thái tài khoản.' : 'Tạo tài khoản và chọn sinh viên liên kết.' ?></p>
    </div>
</section>
<section class="card">
    <form class="form form-grid" data-user-form<?= $isEdit ? ' data-user-id="' . e($id) . '"' : '' ?>>
        <div class="alert alert-danger" data-form-error hidden></div>
        <div><label>Tên đăng nhập</label><input class="form-control" name="username"<?= $isEdit ? ' disabled' : ' required' ?>></div>
        <?php if (!$isEdit): ?>
            <div><label>Mật khẩu ban đầu</label><input class="form-control" name="password" type="password" required></div>
        <?php endif; ?>
        <div><label>Họ tên</label><input class="form-control" name="full_name"></div>
        <div><label>Email</label><input class="form-control" name="email" type="email" required></div>
        <div><label>Vai trò</label><select class="form-select" name="role"><option value="user">Người dùng</option><option value="admin">Quản trị viên</option></select></div>
        <div><label>Sinh viên liên kết</label><select class="form-select" name="student_id" data-student-account-select><option value="">-- Chọn sinh viên --</option></select></div>
        <div><label>Trạng thái</label><select class="form-select" name="status"><option value="active">Đang hoạt động</option><option value="locked">Đã khóa</option></select></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= $isEdit ? 'Cập nhật' : 'Tạo tài khoản' ?></button><a class="btn btn-secondary" href="users.php">Hủy</a></div>
    </form>
</section>
<?php render_footer(); ?>
