<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login();
if (!is_admin()) {
    redirect('profile.php');
}

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
        <p>Thông tin cá nhân và kết quả học tập.</p>
    </div>
    <div class="form-actions">
        <a class="btn btn-secondary" href="students.php">Quay lại</a>
        <?php if (is_admin()): ?>
            <a class="btn btn-warning" href="student_form.php?id=<?= e($id) ?>">Sửa</a>
            <button class="btn btn-danger" type="button" data-delete-student="<?= e($id) ?>">Xóa</button>
        <?php endif; ?>
    </div>
</section>
<section class="detail-grid" data-student-detail data-student-id="<?= e($id) ?>">
    <div class="card">Đang tải dữ liệu...</div>
</section>
<section class="card">
    <h2>Điểm 3 môn học</h2>
    <form data-grades-form data-student-id="<?= e($id) ?>" data-admin="<?= is_admin() ? '1' : '0' ?>">
        <div class="alert alert-danger" data-form-error hidden></div>
        <table class="table grades-table">
            <thead><tr><th>Mã môn</th><th>Môn học</th><th>Điểm</th></tr></thead>
            <tbody data-student-grades><tr><td colspan="3" class="empty-state">Đang tải dữ liệu...</td></tr></tbody>
        </table>
        <?php if (is_admin()): ?>
            <div class="form-actions grades-actions"><button class="btn btn-primary" type="submit">Lưu điểm</button></div>
        <?php endif; ?>
    </form>
</section>
<?php render_footer(); ?>
