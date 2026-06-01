<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/user.php';

require_login();

$errors = [];

if (is_post()) {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $user = get_user_by_id((int) $_SESSION['user_id']);

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        $errors['current_password'] = 'Mật khẩu hiện tại không đúng.';
    }
    if (strlen($newPassword) < 6) {
        $errors['new_password'] = 'Mật khẩu mới phải tối thiểu 6 ký tự.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Nhập lại mật khẩu mới không khớp.';
    }

    if ($errors === []) {
        try {
            change_user_password((int) $_SESSION['user_id'], $newPassword);
            write_log('INFO', 'PASSWORD_CHANGED', 'Password changed');
            set_flash('success', 'Đã đổi mật khẩu thành công.');
            redirect('profile.php');
        } catch (Throwable $exception) {
            write_log('ERROR', 'EXCEPTION', 'Failed to change password', ['error' => $exception->getMessage()]);
            $errors['form'] = generic_error_message();
        }
    }
}

render_header('Đổi mật khẩu');
?>
<section class="page-header">
    <div>
        <h1>Đổi mật khẩu</h1>
        <p>Mật khẩu mới phải có tối thiểu 6 ký tự.</p>
    </div>
</section>

<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

<section class="card">
    <form method="post" class="form">
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
