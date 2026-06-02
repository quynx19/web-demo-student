<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/grade.php';
require_once __DIR__ . '/../app/student.php';
require_once __DIR__ . '/../app/user.php';

require_login();

$userId = (int) $_SESSION['user_id'];
$user = get_user_by_id($userId);
if ($user === null) {
    logout_current_user();
    redirect('login.php');
}
$student = $user['student_id'] === null ? null : get_student((int) $user['student_id']);
$profileErrors = [];
$passwordErrors = [];

if (is_post()) {
    $action = field_value('action', $_POST);
    if (!valid_csrf_token()) {
        $profileErrors['form'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } elseif ($action === 'password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        if (!password_verify($currentPassword, $user['password_hash'])) {
            $passwordErrors['current_password'] = 'Mật khẩu hiện tại không đúng.';
        }
        if (strlen($newPassword) < 6) {
            $passwordErrors['new_password'] = 'Mật khẩu mới phải tối thiểu 6 ký tự.';
        }
        if ($newPassword !== (string) ($_POST['confirm_password'] ?? '')) {
            $passwordErrors['confirm_password'] = 'Nhập lại mật khẩu mới không khớp.';
        }
        if ($passwordErrors === []) {
            change_user_password($userId, $newPassword);
            write_log('INFO', 'PASSWORD_CHANGED', 'Password changed');
            redirect('profile.php');
        }
    } elseif ($action === 'profile') {
        $profile = ['full_name' => field_value('full_name', $_POST), 'email' => field_value('email', $_POST)];
        if ($profile['email'] === '' || !filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
            $profileErrors['email'] = 'Email không đúng định dạng.';
        }
        if ($student !== null) {
            $studentProfile = array_merge($student, $profile, ['phone' => field_value('phone', $_POST, (string) $student['phone'])]);
            $profileErrors = array_merge($profileErrors, validate_student($studentProfile, (int) $student['id']));
        }
        if ($profileErrors === []) {
            update_current_user_profile($userId, $profile);
            if ($student !== null) {
                update_student((int) $student['id'], $studentProfile);
            }
            $_SESSION['full_name'] = $profile['full_name'];
            $_SESSION['email'] = $profile['email'];
            setcookie('theme', ($_POST['theme'] ?? '') === 'dark' ? 'dark' : 'light', ['expires' => time() + 30 * 24 * 60 * 60, 'path' => '/', 'samesite' => 'Lax']);
            write_log('INFO', 'PROFILE_UPDATED', 'Profile updated');
            redirect('profile.php');
        }
    }
}

$user = get_user_by_id($userId) ?: $user;
$student = $user['student_id'] === null ? null : get_student((int) $user['student_id']);
$profile = $student === null ? $user : array_merge($user, $student);
$grades = $student === null ? [] : list_student_grades((int) $student['id']);

write_log('INFO', 'PROFILE_VIEW', 'Profile viewed');
render_header('Hồ sơ cá nhân');
?>
<section class="page-header"><div><h1>Hồ sơ cá nhân</h1><p>Thông tin cá nhân, tài khoản và kết quả học tập.</p></div></section>
<section class="profile-grid">
    <?php foreach (['username' => 'Tên đăng nhập', 'student_code' => 'Mã sinh viên', 'full_name' => 'Họ tên', 'email' => 'Email', 'phone' => 'Số điện thoại', 'major' => 'Ngành học', 'year' => 'Năm học', 'role' => 'Vai trò', 'status' => 'Trạng thái'] as $key => $label): ?>
        <?php if (array_key_exists($key, $profile)): ?><div class="card"><span class="meta-label"><?= e($label) ?></span><strong><?= e($profile[$key]) ?></strong></div><?php endif; ?>
    <?php endforeach; ?>
</section>
<section class="card">
    <h2>Cập nhật hồ sơ</h2>
    <?php if (isset($profileErrors['form'])): ?><div class="alert alert-danger"><?= e($profileErrors['form']) ?></div><?php endif; ?>
    <form method="post" class="form form-grid"><?= csrf_input() ?><input type="hidden" name="action" value="profile">
        <div><label for="full_name">Họ tên</label><input class="form-control" id="full_name" name="full_name" value="<?= e($profile['full_name']) ?>"></div>
        <div><label for="email">Email</label><input class="form-control" id="email" name="email" type="email" value="<?= e($profile['email']) ?>" required><?php if (isset($profileErrors['email'])): ?><span class="field-error"><?= e($profileErrors['email']) ?></span><?php endif; ?></div>
        <?php if ($student !== null): ?><div><label for="phone">Số điện thoại</label><input class="form-control" id="phone" name="phone" value="<?= e($student['phone']) ?>"></div><?php endif; ?>
        <div class="form-actions theme-form"><label><input type="radio" name="theme" value="light" <?= current_theme() === 'light' ? 'checked' : '' ?>> Giao diện sáng</label><label><input type="radio" name="theme" value="dark" <?= current_theme() === 'dark' ? 'checked' : '' ?>> Giao diện tối</label></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Lưu thay đổi</button></div>
    </form>
</section>
<section class="card">
    <h2>Đổi mật khẩu</h2>
    <form method="post" class="form"><?= csrf_input() ?><input type="hidden" name="action" value="password">
        <label for="current_password">Mật khẩu hiện tại</label><input class="form-control" id="current_password" name="current_password" type="password" required><?php if (isset($passwordErrors['current_password'])): ?><span class="field-error"><?= e($passwordErrors['current_password']) ?></span><?php endif; ?>
        <label for="new_password">Mật khẩu mới</label><input class="form-control" id="new_password" name="new_password" type="password" required><?php if (isset($passwordErrors['new_password'])): ?><span class="field-error"><?= e($passwordErrors['new_password']) ?></span><?php endif; ?>
        <label for="confirm_password">Nhập lại mật khẩu mới</label><input class="form-control" id="confirm_password" name="confirm_password" type="password" required><?php if (isset($passwordErrors['confirm_password'])): ?><span class="field-error"><?= e($passwordErrors['confirm_password']) ?></span><?php endif; ?>
        <button class="btn btn-primary" type="submit">Đổi mật khẩu</button>
    </form>
</section>
<?php if ($grades !== []): ?><section class="card"><h2>Điểm 3 môn học</h2><table class="table grades-table"><thead><tr><th>Mã môn</th><th>Môn học</th><th>Điểm</th></tr></thead><tbody><?php foreach ($grades as $grade): ?><tr><td><?= e($grade['subject_code']) ?></td><td><?= e($grade['subject_name']) ?></td><td><?= e($grade['score']) ?></td></tr><?php endforeach; ?></tbody></table></section><?php endif; ?>
<?php render_footer(); ?>
