<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/student.php';

require_role('admin');

$errors = [];
$form = [
    'student_code' => '',
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'major' => '',
    'year' => '',
];

if (is_post()) {
    $form = array_merge($form, $_POST);
    $errors = validate_student($form);

    if ($errors === []) {
        try {
            $id = create_student($form);
            write_log('INFO', 'STUDENT_CREATED', 'Student created', ['student_id' => $id]);
            set_flash('success', 'Đã thêm sinh viên thành công.');
            redirect('students.php');
        } catch (Throwable $exception) {
            write_log('ERROR', 'EXCEPTION', 'Failed to create student', ['error' => $exception->getMessage()]);
            $errors['form'] = generic_error_message();
        }
    }
}

render_header('Thêm sinh viên');
?>
<section class="page-header">
    <div>
        <h1>Thêm sinh viên</h1>
        <p>Nhập thông tin cơ bản của sinh viên mới.</p>
    </div>
    <a class="btn btn-secondary" href="students.php">Quay lại</a>
</section>

<?php if (isset($errors['form'])): ?>
    <div class="alert alert-danger"><?= e($errors['form']) ?></div>
<?php endif; ?>

<section class="card">
    <form method="post" class="form form-grid">
        <?php render_student_form_fields($form, $errors); ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Lưu sinh viên</button>
            <a class="btn btn-secondary" href="students.php">Hủy</a>
        </div>
    </form>
</section>
<?php render_footer(); ?>
