<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/user.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$user = $id > 0 ? get_user_by_id($id) : null;
if ($user === null) {
    set_flash('error', 'Không tìm thấy tài khoản.');
    redirect('users.php');
}

$errors = [];
$form = $user;

if (is_post()) {
    $form = array_merge($form, $_POST, ['username' => $user['username']]);
    if ($id === (int) $_SESSION['user_id'] && field_value('status', $form) === 'locked') {
        $errors['status'] = 'Không thể tự khóa tài khoản đang đăng nhập.';
    }
    $errors = array_merge($errors, validate_user_data($form, false, $id));

    if ($errors === []) {
        try {
            update_user($id, $form);
            write_log('INFO', 'USER_UPDATED', 'User updated', ['target_user_id' => $id]);
            set_flash('success', 'Đã cập nhật tài khoản.');
            redirect('users.php');
        } catch (Throwable $exception) {
            write_log('ERROR', 'EXCEPTION', 'Failed to update user', ['error' => $exception->getMessage(), 'target_user_id' => $id]);
            $errors['form'] = generic_error_message();
        }
    }
}

render_header('Sửa tài khoản');
?>
<section class="page-header"><div><h1>Sửa tài khoản</h1><p>Cập nhật thông tin tài khoản.</p></div></section>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<section class="card">
    <form method="post" class="form form-grid">
        <div><label>Tên đăng nhập</label><input class="form-control" value="<?= e($user['username']) ?>" disabled></div>
        <div><label>Họ tên</label><input class="form-control" name="full_name" value="<?= e($form['full_name']) ?>"></div>
        <div><label>Email</label><input class="form-control" name="email" type="email" value="<?= e($form['email']) ?>" required><?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?></div>
        <div><label>Vai trò</label><select class="form-select" name="role"><option value="user" <?= $form['role'] === 'user' ? 'selected' : '' ?>>Người dùng</option><option value="admin" <?= $form['role'] === 'admin' ? 'selected' : '' ?>>Quản trị viên</option></select></div>
        <div><label>Trạng thái</label><select class="form-select" name="status"><option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Đang hoạt động</option><option value="locked" <?= $form['status'] === 'locked' ? 'selected' : '' ?>>Đã khóa</option></select><?php if (isset($errors['status'])): ?><span class="field-error"><?= e($errors['status']) ?></span><?php endif; ?></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Cập nhật</button><a class="btn btn-secondary" href="users.php">Hủy</a></div>
    </form>
</section>
<?php render_footer(); ?>
