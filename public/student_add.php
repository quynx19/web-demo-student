<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_role('admin');

$form = [
    'student_code' => '',
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'major' => '',
    'year' => '',
];

render_header('Thêm sinh viên');
?>
<section class="page-header">
    <div>
        <h1>Thêm sinh viên</h1>
        <p>Tạo sinh viên qua REST API.</p>
    </div>
    <a class="btn btn-secondary" href="students.php">Quay lại</a>
</section>
<section class="card">
    <form class="form form-grid" data-student-form>
        <div class="alert alert-danger" data-form-error hidden></div>
        <?php render_student_form_fields($form, []); ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Lưu sinh viên</button>
            <a class="btn btn-secondary" href="students.php">Hủy</a>
        </div>
    </form>
</section>
<?php render_footer(); ?>
