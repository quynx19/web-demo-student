<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_role('admin');

$rawId = $_GET['id'] ?? '';
$id = (int) $rawId;
if ($rawId !== '' && $id <= 0) {
    set_flash('Sinh viên không hợp lệ.');
    redirect('students.php');
}

$isEdit = $id > 0;
$form = [
    'student_code' => '',
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'major' => '',
    'year' => '',
];

render_header($isEdit ? 'Sửa sinh viên' : 'Thêm sinh viên');
?>
<section class="page-header">
    <div>
        <h1><?= $isEdit ? 'Sửa sinh viên' : 'Thêm sinh viên' ?></h1>
        <p><?= $isEdit ? 'Chỉnh sửa thông tin hồ sơ sinh viên.' : 'Nhập thông tin để tạo hồ sơ sinh viên mới.' ?></p>
    </div>
    <a class="btn btn-secondary" href="students.php">Quay lại</a>
</section>
<section class="card">
    <form class="form form-grid" data-student-form<?= $isEdit ? ' data-student-id="' . e($id) . '"' : '' ?>>
        <div class="alert alert-danger" data-form-error hidden></div>
        <?php render_student_form_fields($form, []); ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Cập nhật' : 'Lưu sinh viên' ?></button>
            <a class="btn btn-secondary" href="students.php">Hủy</a>
        </div>
    </form>
</section>
<?php render_footer(); ?>
