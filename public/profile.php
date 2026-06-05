<?php

declare(strict_types=1);

// Hồ sơ cá nhân: hiển thị thông tin sinh viên, điểm và xử lý cập nhật hồ sơ.
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

if (is_post()) {
    $profileForm = [
        'full_name' => field_value('full_name', $_POST),
        'email' => field_value('email', $_POST),
    ];

    if ($profileForm['email'] === '' || !filter_var($profileForm['email'], FILTER_VALIDATE_EMAIL)) {
        $profileErrors['email'] = 'Email không đúng định dạng.';
    }

    if ($student !== null) {
        $studentForm = array_merge($student, $profileForm, [
            'phone' => field_value('phone', $_POST, (string) $student['phone']),
        ]);
        $profileErrors = array_merge($profileErrors, validate_student($studentForm, (int) $student['id']));
    }

    if ($profileErrors === []) {
        update_current_user_profile($userId, $profileForm);
        if ($student !== null) {
            update_student((int) $student['id'], $studentForm);
        }
        setcookie('theme', ($_POST['theme'] ?? '') === 'dark' ? 'dark' : 'light', [
            'expires' => time() + 30 * 24 * 60 * 60,
            'path' => '/',
            'samesite' => 'Lax',
        ]);
        set_flash('Hồ sơ đã được cập nhật.', 'success');
        redirect('profile.php');
    }
}

$user = get_user_by_id($userId) ?: $user;
$student = $user['student_id'] === null ? null : get_student((int) $user['student_id']);
$profile = $student === null ? $user : array_merge($user, $student);
$grades = $student === null ? [] : list_student_grades((int) $student['id']);

render_header('Hồ sơ cá nhân');
?>
<section class="page-header">
    <div>
        <h1>Hồ sơ cá nhân</h1>
        <p>Thông tin cá nhân, tài khoản và kết quả học tập.</p>
    </div>
    <a class="btn btn-primary" href="password.php">Đổi mật khẩu</a>
</section>
<?php render_flash(); ?>
<section class="profile-grid">
    <?php foreach (['username' => 'Tên đăng nhập', 'student_code' => 'Mã sinh viên', 'full_name' => 'Họ tên', 'email' => 'Email', 'phone' => 'Số điện thoại', 'major' => 'Ngành học', 'year' => 'Năm học', 'role' => 'Vai trò', 'status' => 'Trạng thái'] as $key => $label): ?>
        <?php if (array_key_exists($key, $profile)): ?>
            <div class="card">
                <span class="meta-label"><?= e($label) ?></span>
                <strong><?= e($profile[$key]) ?></strong>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</section>
<section class="card">
    <h2>Cập nhật hồ sơ</h2>
    <?php if (isset($profileErrors['form'])): ?><div class="alert alert-danger"><?= e($profileErrors['form']) ?></div><?php endif; ?>
    <form method="post" class="form form-grid">
        <div>
            <label for="full_name">Họ tên</label>
            <input class="form-control" id="full_name" name="full_name" value="<?= e($profile['full_name']) ?>">
            <?php if (isset($profileErrors['full_name'])): ?><span class="field-error"><?= e($profileErrors['full_name']) ?></span><?php endif; ?>
        </div>
        <div>
            <label for="email">Email</label>
            <input class="form-control" id="email" name="email" type="email" value="<?= e($profile['email']) ?>" required>
            <?php if (isset($profileErrors['email'])): ?><span class="field-error"><?= e($profileErrors['email']) ?></span><?php endif; ?>
        </div>
        <?php if ($student !== null): ?>
            <div>
                <label for="phone">Số điện thoại</label>
                <input class="form-control" id="phone" name="phone" value="<?= e($student['phone']) ?>">
                <?php if (isset($profileErrors['phone'])): ?><span class="field-error"><?= e($profileErrors['phone']) ?></span><?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="form-actions theme-form">
            <label><input type="radio" name="theme" value="light" <?= current_theme() === 'light' ? 'checked' : '' ?>> Giao diện sáng</label>
            <label><input type="radio" name="theme" value="dark" <?= current_theme() === 'dark' ? 'checked' : '' ?>> Giao diện tối</label>
        </div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Lưu thay đổi</button></div>
    </form>
</section>
<?php if ($grades !== []): ?>
    <section class="card">
        <h2>Điểm 3 môn học</h2>
        <table class="table grades-table">
            <thead><tr><th>Mã môn</th><th>Môn học</th><th>Điểm</th></tr></thead>
            <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr><td><?= e($grade['subject_code']) ?></td><td><?= e($grade['subject_name']) ?></td><td><?= e($grade['score']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>
<?php render_footer(); ?>
