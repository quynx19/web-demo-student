<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/student.php';
require_once __DIR__ . '/../app/user.php';

require_login();
write_log('INFO', 'DASHBOARD_VIEW', 'Dashboard viewed');

$studentCount = 0;
$userCount = 0;
$majorStats = [];
$recentStudents = [];
$recentLogCount = 0;
$error = '';

try {
    $studentCount = count_students();
    $majorStats = count_students_by_major();
    $recentStudents = list_students([], 5, 0);
    if (is_admin()) {
        $userCount = count_users();
        $logFile = __DIR__ . '/../logs/app.log';
        $recentLogCount = is_file($logFile) ? count(array_slice(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [], -100)) : 0;
    }
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'Failed to load dashboard', ['error' => $exception->getMessage()]);
    $error = generic_error_message();
}

render_header('Tổng quan');
?>
<section class="page-header">
    <div>
        <h1>Xin chào, <?= e($_SESSION['username'] ?? '') ?></h1>
        <p>Vai trò: <strong><?= e(role_label($_SESSION['role'] ?? '')) ?></strong></p>
    </div>
    <a class="btn btn-primary" href="students.php">Xem danh sách sinh viên</a>
</section>

<?php render_flash(); ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Tổng số sinh viên</span>
        <strong><?= e($studentCount) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Số ngành học</span>
        <strong><?= e(count($majorStats)) ?></strong>
    </article>
    <?php if (is_admin()): ?>
        <article class="stat-card">
            <span class="stat-label">Tổng số tài khoản</span>
            <strong><?= e($userCount) ?></strong>
        </article>
        <article class="stat-card">
            <span class="stat-label">Log gần đây</span>
            <strong><?= e($recentLogCount) ?></strong>
        </article>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Thao tác nhanh</h2>
    <div class="quick-links">
        <a href="students.php">Xem danh sách sinh viên</a>
        <?php if (is_admin()): ?>
            <a href="student_add.php">Thêm sinh viên</a>
            <a href="users.php">Quản lý tài khoản</a>
            <a href="logs.php">Xem nhật ký ứng dụng</a>
        <?php endif; ?>
        <a href="profile.php">Hồ sơ cá nhân</a>
    </div>
</section>

<section class="card">
    <h2>Sinh viên theo ngành học</h2>
    <div class="mini-table">
        <?php foreach ($majorStats as $row): ?>
            <div>
                <span><?= e($row['major']) ?></span>
                <strong><?= e($row['total']) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="table-card">
    <div class="table-title">
        <h2>Sinh viên mới cập nhật gần đây</h2>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Mã sinh viên</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Ngành học</th>
                <th>Năm học</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentStudents as $student): ?>
                <tr>
                    <td><?= e($student['student_code']) ?></td>
                    <td><?= e($student['full_name']) ?></td>
                    <td><?= e($student['email']) ?></td>
                    <td><?= e($student['major']) ?></td>
                    <td><?= e($student['year']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
