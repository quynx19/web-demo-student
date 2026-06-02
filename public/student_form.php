<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/grade.php';
require_once __DIR__ . '/../app/student.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$student = $isEdit ? get_student($id) : null;
if ($isEdit && $student === null) {
    set_flash('Không tìm thấy sinh viên.');
    redirect('students.php');
}

$form = $student ?? ['student_code' => '', 'full_name' => '', 'email' => '', 'phone' => '', 'major' => '', 'year' => ''];
$errors = [];
if (is_post()) {
    $form = normalize_student_data($_POST);
    if (!valid_csrf_token()) {
        $errors['form'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } else {
        $errors = validate_student($form, $isEdit ? $id : null);
    }

    if ($errors === []) {
        if ($isEdit) {
            update_student($id, $form);
            write_log('INFO', 'STUDENT_UPDATED', 'Student updated', ['student_id' => $id]);
        } else {
            $id = create_student($form);
            initialize_student_grades($id);
            write_log('INFO', 'STUDENT_CREATED', 'Student created', ['student_id' => $id]);
        }
        redirect('students.php');
    }
}

render_header($isEdit ? 'Sửa sinh viên' : 'Thêm sinh viên');
?>
<section class="page-header"><div><h1><?= $isEdit ? 'Sửa sinh viên' : 'Thêm sinh viên' ?></h1><p><?= $isEdit ? 'Chỉnh sửa thông tin hồ sơ sinh viên.' : 'Nhập thông tin để tạo hồ sơ sinh viên mới.' ?></p></div><a class="btn btn-secondary" href="students.php">Quay lại</a></section>
<section class="card">
    <?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
    <form method="post" class="form form-grid">
        <?= csrf_input() ?>
        <?php render_student_form_fields($form, $errors); ?>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= $isEdit ? 'Cập nhật' : 'Lưu sinh viên' ?></button><a class="btn btn-secondary" href="students.php">Hủy</a></div>
    </form>
</section>
<?php render_footer(); ?>
