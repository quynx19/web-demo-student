<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/student.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Sinh viên không hợp lệ.');
    redirect('students.php');
}

$errors = [];
$student = null;

try {
    $student = get_student($id);
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'Failed to load student for edit', ['error' => $exception->getMessage(), 'student_id' => $id]);
}

if ($student === null) {
    set_flash('error', 'Không tìm thấy sinh viên.');
    redirect('students.php');
}

$form = $student;

if (is_post()) {
    $form = array_merge($form, $_POST);
    $errors = validate_student($form);

    if ($errors === []) {
        try {
            update_student($id, $form);
            write_log('INFO', 'STUDENT_UPDATED', 'Student updated', ['student_id' => $id]);
            set_flash('success', 'Đã cập nhật sinh viên thành công.');
            redirect('students.php');
        } catch (Throwable $exception) {
            write_log('ERROR', 'EXCEPTION', 'Failed to update student', ['error' => $exception->getMessage(), 'student_id' => $id]);
            $errors['form'] = generic_error_message();
        }
    }
}

render_header('Sửa sinh viên');
?>
<section class="page-header">
    <div>
        <h1>Sửa sinh viên</h1>
        <p>Cập nhật thông tin hồ sơ sinh viên.</p>
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
            <button class="btn btn-primary" type="submit">Cập nhật</button>
            <a class="btn btn-secondary" href="students.php">Hủy</a>
        </div>
    </form>
</section>
<?php render_footer(); ?>
