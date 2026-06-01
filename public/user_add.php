<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/user.php';

require_role('admin');

$errors = [];
$form = ['username' => '', 'full_name' => '', 'email' => '', 'role' => 'user', 'status' => 'active'];

if (is_post()) {
    $form = array_merge($form, $_POST);
    $errors = validate_user_data($form, true);

    if ($errors === []) {
        try {
            $id = create_user($form);
            write_log('INFO', 'USER_CREATED', 'User created', ['target_user_id' => $id, 'username' => field_value('username', $form)]);
            set_flash('success', 'Đã tạo tài khoản mới.');
            redirect('users.php');
        } catch (Throwable $exception) {
            write_log('ERROR', 'EXCEPTION', 'Failed to create user', ['error' => $exception->getMessage()]);
            $errors['form'] = generic_error_message();
        }
    }
}

render_header('Thêm tài khoản');
?>
<section class="page-header"><div><h1>Thêm tài khoản</h1><p>Tạo tài khoản mới cho hệ thống.</p></div></section>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<section class="card">
    <form method="post" class="form form-grid">
        <div><label>Tên đăng nhập</label><input class="form-control" name="username" value="<?= e($form['username']) ?>" required><?php if (isset($errors['username'])): ?><span class="field-error"><?= e($errors['username']) ?></span><?php endif; ?></div>
        <div><label>Mật khẩu ban đầu</label><input class="form-control" name="password" type="password" required><?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?></div>
        <div><label>Họ tên</label><input class="form-control" name="full_name" value="<?= e($form['full_name']) ?>"></div>
        <div><label>Email</label><input class="form-control" name="email" type="email" value="<?= e($form['email']) ?>" required><?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?></div>
        <div><label>Vai trò</label><select class="form-select" name="role"><option value="user" <?= $form['role'] === 'user' ? 'selected' : '' ?>>Người dùng</option><option value="admin" <?= $form['role'] === 'admin' ? 'selected' : '' ?>>Quản trị viên</option></select></div>
        <div><label>Trạng thái</label><select class="form-select" name="status"><option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Đang hoạt động</option><option value="locked" <?= $form['status'] === 'locked' ? 'selected' : '' ?>>Đã khóa</option></select></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Tạo tài khoản</button><a class="btn btn-secondary" href="users.php">Hủy</a></div>
    </form>
</section>
<?php render_footer(); ?>
