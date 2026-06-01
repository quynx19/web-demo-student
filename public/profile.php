<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/user.php';

require_login();

$user = get_user_by_id((int) $_SESSION['user_id']);
if ($user === null) {
    logout_current_user();
    redirect('login.php');
}

$errors = [];
$form = $user;

if (is_post()) {
    $form = array_merge($form, $_POST);
    $email = field_value('email', $form);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không đúng định dạng.';
    }

    if ($errors === []) {
        try {
            update_current_user_profile((int) $_SESSION['user_id'], $form);
            $_SESSION['full_name'] = field_value('full_name', $form);
            $_SESSION['email'] = $email;

            $theme = ($_POST['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
            setcookie('theme', $theme, [
                'expires' => time() + 30 * 24 * 60 * 60,
                'path' => '/',
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
            $_COOKIE['theme'] = $theme;

            write_log('INFO', 'PROFILE_UPDATED', 'Profile updated');
            set_flash('success', 'Đã cập nhật hồ sơ.');
            redirect('profile.php');
        } catch (Throwable $exception) {
            write_log('ERROR', 'EXCEPTION', 'Failed to update profile', ['error' => $exception->getMessage()]);
            $errors['form'] = generic_error_message();
        }
    }
}

write_log('INFO', 'PROFILE_VIEW', 'Profile viewed');

render_header('Hồ sơ cá nhân');
?>
<section class="page-header">
    <div>
        <h1>Hồ sơ cá nhân</h1>
        <p>Thông tin tài khoản, hồ sơ cá nhân và tùy chọn giao diện.</p>
    </div>
    <a class="btn btn-primary" href="change_password.php">Đổi mật khẩu</a>
</section>

<?php render_flash(); ?>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>

<section class="profile-grid">
    <div class="card">
        <span class="meta-label">Tên đăng nhập</span>
        <strong><?= e($user['username']) ?></strong>
    </div>
    <div class="card">
        <span class="meta-label">Họ tên</span>
        <strong><?= e($user['full_name']) ?></strong>
    </div>
    <div class="card">
        <span class="meta-label">Email</span>
        <strong><?= e($user['email']) ?></strong>
    </div>
    <div class="card">
        <span class="meta-label">Vai trò</span>
        <strong><?= e(role_label($user['role'])) ?></strong>
    </div>
    <div class="card">
        <span class="meta-label">Trạng thái</span>
        <strong><?= e($user['status'] === 'active' ? 'Đang hoạt động' : 'Đã khóa') ?></strong>
    </div>
</section>

<section class="card">
    <h2>Cập nhật hồ sơ</h2>
    <form method="post" class="form form-grid">
        <div>
            <label for="full_name">Họ tên</label>
            <input class="form-control" id="full_name" name="full_name" type="text" value="<?= e($form['full_name'] ?? '') ?>">
        </div>
        <div>
            <label for="email">Email</label>
            <input class="form-control" id="email" name="email" type="email" value="<?= e($form['email'] ?? '') ?>" required>
            <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
        </div>
        <div class="form-actions theme-form">
            <label><input type="radio" name="theme" value="light" <?= current_theme() === 'light' ? 'checked' : '' ?>> Giao diện sáng</label>
            <label><input type="radio" name="theme" value="dark" <?= current_theme() === 'dark' ? 'checked' : '' ?>> Giao diện tối</label>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
        </div>
    </form>
</section>
<?php render_footer(); ?>
