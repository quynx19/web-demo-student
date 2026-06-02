<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('Sinh viên không hợp lệ.');
    redirect('students.php');
}

$form = [
    'student_code' => '',
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'major' => '',
    'year' => '',
];

render_header('Sửa sinh viên');
?>
<section class="page-header">
    <div>
        <h1>Sửa sinh viên</h1>
        <p>Cập nhật sinh viên qua REST API.</p>
    </div>
    <a class="btn btn-secondary" href="students.php">Quay lại</a>
</section>
<section class="card">
    <form class="form form-grid" data-student-form data-student-id="<?= e($id) ?>">
        <div class="alert alert-danger" data-form-error hidden></div>
        <?php render_student_form_fields($form, []); ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Cập nhật</button>
            <a class="btn btn-secondary" href="students.php">Hủy</a>
        </div>
    </form>
</section>
<?php render_footer(); ?>
