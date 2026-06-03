<?php

declare(strict_types=1);

// Form tài khoản: dùng chung cho tạo và chỉnh sửa tài khoản người dùng.
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/student.php';
require_once __DIR__ . '/../app/user.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$user = $isEdit ? get_user_by_id($id) : null;
if ($isEdit && $user === null) {
    set_flash('Không tìm thấy tài khoản.');
    redirect('users.php');
}

$form = $user ?? ['username' => '', 'full_name' => '', 'email' => '', 'role' => 'user', 'status' => 'active', 'student_id' => ''];
$errors = [];
if (is_post()) {
    $form = array_merge($form, $_POST);
    if (!valid_csrf_token()) {
        $errors['form'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } else {
        $errors = validate_user_data($form, !$isEdit, $isEdit ? $id : null);
        if ($isEdit && $id === (int) $_SESSION['user_id'] && field_value('status', $form) === 'locked') {
            $errors['status'] = 'Không thể tự khóa tài khoản đang đăng nhập.';
        }
    }

    if ($errors === []) {
        if ($isEdit) {
            update_user($id, $form);
            write_log('INFO', 'USER_UPDATED', 'User updated', ['target_user_id' => $id]);
        } else {
            $id = create_user($form);
            write_log('INFO', 'USER_CREATED', 'User created', ['target_user_id' => $id]);
        }
        redirect('users.php');
    }
}

$students = list_students();
render_header($isEdit ? 'Sửa tài khoản' : 'Thêm tài khoản');
?>
<section class="page-header"><div><h1><?= $isEdit ? 'Sửa tài khoản' : 'Thêm tài khoản' ?></h1><p><?= $isEdit ? 'Chỉnh sửa thông tin và trạng thái tài khoản.' : 'Tạo tài khoản và chọn sinh viên liên kết.' ?></p></div></section>
<section class="card">
    <?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
    <form method="post" class="form form-grid">
        <?= csrf_input() ?>
        <div><label>Tên đăng nhập</label><input class="form-control" name="username" value="<?= e($form['username']) ?>" <?= $isEdit ? 'readonly' : 'required' ?>><?php if (isset($errors['username'])): ?><span class="field-error"><?= e($errors['username']) ?></span><?php endif; ?></div>
        <?php if (!$isEdit): ?><div><label>Mật khẩu ban đầu</label><input class="form-control" name="password" type="password" required><?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?></div><?php endif; ?>
        <div><label>Họ tên</label><input class="form-control" name="full_name" value="<?= e($form['full_name']) ?>"></div>
        <div><label>Email</label><input class="form-control" name="email" type="email" value="<?= e($form['email']) ?>" required><?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?></div>
        <div><label>Vai trò</label><select class="form-select" name="role"><option value="user" <?= $form['role'] === 'user' ? 'selected' : '' ?>>Người dùng</option><option value="admin" <?= $form['role'] === 'admin' ? 'selected' : '' ?>>Quản trị viên</option></select></div>
        <div><label>Sinh viên liên kết</label><select class="form-select" name="student_id"><option value="">-- Chọn sinh viên --</option><?php foreach ($students as $student): ?><option value="<?= e($student['id']) ?>" <?= (string) $form['student_id'] === (string) $student['id'] ? 'selected' : '' ?>><?= e($student['student_code'] . ' - ' . $student['full_name']) ?></option><?php endforeach; ?></select><?php if (isset($errors['student_id'])): ?><span class="field-error"><?= e($errors['student_id']) ?></span><?php endif; ?></div>
        <div><label>Trạng thái</label><select class="form-select" name="status"><option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Đang hoạt động</option><option value="locked" <?= $form['status'] === 'locked' ? 'selected' : '' ?>>Đã khóa</option></select><?php if (isset($errors['status'])): ?><span class="field-error"><?= e($errors['status']) ?></span><?php endif; ?></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= $isEdit ? 'Cập nhật' : 'Tạo tài khoản' ?></button><a class="btn btn-secondary" href="users.php">Hủy</a></div>
    </form>
</section>
<?php render_footer(); ?>
