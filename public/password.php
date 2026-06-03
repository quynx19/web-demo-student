<?php

declare(strict_types=1);

// Đổi mật khẩu: xác thực mật khẩu cũ và cập nhật mật khẩu mới cho tài khoản hiện tại.
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/user.php';

require_login();

$userId = (int) $_SESSION['user_id'];
$user = get_user_by_id($userId);
if ($user === null) {
    logout_current_user();
    redirect('login.php');
}

$errors = [];

if (is_post()) {
    if (!valid_csrf_token()) {
        $errors['form'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } else {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $errors['current_password'] = 'Mật khẩu hiện tại không đúng.';
        }

        if (strlen($newPassword) < 6) {
            $errors['new_password'] = 'Mật khẩu mới phải tối thiểu 6 ký tự.';
        }

        if ($newPassword !== (string) ($_POST['confirm_password'] ?? '')) {
            $errors['confirm_password'] = 'Nhập lại mật khẩu mới không khớp.';
        }

        if ($errors === []) {
            change_user_password($userId, $newPassword);
            set_flash('Mật khẩu đã được cập nhật.', 'success');
            write_log('INFO', 'PASSWORD_CHANGED', 'Password changed');
            redirect('profile.php');
        }
    }
}

render_header('Đổi mật khẩu');
?>
<section class="page-header">
    <div>
        <h1>Đổi mật khẩu</h1>
        <p>Cập nhật mật khẩu đăng nhập cho tài khoản hiện tại.</p>
    </div>
    <a class="btn btn-secondary" href="profile.php">Quay lại hồ sơ</a>
</section>
<section class="card">
    <?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
    <form method="post" class="form">
        <?= csrf_input() ?>
        <label for="current_password">Mật khẩu hiện tại</label>
        <input class="form-control" id="current_password" name="current_password" type="password" required>
        <?php if (isset($errors['current_password'])): ?><span class="field-error"><?= e($errors['current_password']) ?></span><?php endif; ?>

        <label for="new_password">Mật khẩu mới</label>
        <input class="form-control" id="new_password" name="new_password" type="password" required>
        <?php if (isset($errors['new_password'])): ?><span class="field-error"><?= e($errors['new_password']) ?></span><?php endif; ?>

        <label for="confirm_password">Nhập lại mật khẩu mới</label>
        <input class="form-control" id="confirm_password" name="confirm_password" type="password" required>
        <?php if (isset($errors['confirm_password'])): ?><span class="field-error"><?= e($errors['confirm_password']) ?></span><?php endif; ?>

        <button class="btn btn-primary" type="submit">Đổi mật khẩu</button>
    </form>
</section>
<?php render_footer(); ?>
