<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/student.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Sinh viên không hợp lệ.');
    redirect('students.php');
}

try {
    $student = get_student($id);
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'Failed to load student detail', ['error' => $exception->getMessage(), 'student_id' => $id]);
    $student = null;
}

if ($student === null) {
    set_flash('error', 'Không tìm thấy sinh viên.');
    redirect('students.php');
}

write_log('INFO', 'STUDENT_DETAIL_VIEW', 'Student detail viewed', ['student_id' => $id]);

$labels = [
    'id' => 'ID',
    'student_code' => 'Mã sinh viên',
    'full_name' => 'Họ tên',
    'email' => 'Email',
    'phone' => 'Số điện thoại',
    'major' => 'Ngành học',
    'year' => 'Năm học',
    'created_at' => 'Ngày tạo',
    'updated_at' => 'Ngày cập nhật',
];

render_header('Chi tiết sinh viên');
?>
<section class="page-header">
    <div>
        <h1><?= e($student['full_name']) ?></h1>
        <p>Thông tin chi tiết hồ sơ sinh viên.</p>
    </div>
    <div class="form-actions">
        <a class="btn btn-secondary" href="students.php">Quay lại</a>
        <?php if (is_admin()): ?>
            <a class="btn btn-warning" href="student_edit.php?id=<?= e($student['id']) ?>">Sửa</a>
            <a class="btn btn-danger" href="student_delete.php?id=<?= e($student['id']) ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa sinh viên này không?');">Xóa</a>
        <?php endif; ?>
    </div>
</section>

<section class="detail-grid">
    <?php foreach ($student as $key => $value): ?>
        <div class="card">
            <span class="meta-label"><?= e($labels[$key] ?? $key) ?></span>
            <strong><?= e($value) ?></strong>
        </div>
    <?php endforeach; ?>
</section>
<?php render_footer(); ?>
