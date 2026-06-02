<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('Sinh viên không hợp lệ.');
    redirect('students.php');
}

render_header('Chi tiết sinh viên');
?>
<section class="page-header">
    <div>
        <h1 data-student-title>Đang tải...</h1>
        <p>Thông tin chi tiết được tải từ REST API.</p>
    </div>
    <div class="form-actions">
        <a class="btn btn-secondary" href="students.php">Quay lại</a>
        <?php if (is_admin()): ?>
            <a class="btn btn-warning" href="student_edit.php?id=<?= e($id) ?>">Sửa</a>
            <button class="btn btn-danger" type="button" data-delete-student="<?= e($id) ?>">Xóa</button>
        <?php endif; ?>
    </div>
</section>
<section class="detail-grid" data-student-detail data-student-id="<?= e($id) ?>">
    <div class="card">Đang tải dữ liệu...</div>
</section>
<?php render_footer(); ?>
