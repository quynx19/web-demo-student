<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/grade.php';
require_once __DIR__ . '/../app/student.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$student = $id > 0 ? get_student($id) : null;
if ($student === null) {
    set_flash('Không tìm thấy sinh viên.');
    redirect('students.php');
}

$errors = [];
if (is_post()) {
    if (!valid_csrf_token()) {
        $errors['grades'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } else {
        $gradeData = ['grades' => []];
        foreach (grade_subjects() as $subjectCode => $subjectName) {
            $gradeData['grades'][] = ['subject_code' => $subjectCode, 'score' => $_POST['grades'][$subjectCode] ?? ''];
        }
        $errors = validate_student_grades($gradeData);
        if ($errors === []) {
            replace_student_grades($id, $gradeData);
            write_log('INFO', 'STUDENT_GRADES_UPDATED', 'Student grades updated', ['student_id' => $id]);
            redirect('student_detail.php?id=' . $id);
        }
    }
}
$grades = list_student_grades($id);

write_log('INFO', 'STUDENT_VIEW', 'Student detail viewed', ['student_id' => $id]);
render_header('Chi tiết sinh viên');
?>
<section class="page-header">
    <div><h1><?= e($student['full_name']) ?></h1><p>Thông tin cá nhân và kết quả học tập.</p></div>
    <div class="form-actions"><a class="btn btn-secondary" href="students.php">Quay lại</a><a class="btn btn-warning" href="student_form.php?id=<?= e($id) ?>">Sửa</a></div>
</section>
<section class="detail-grid">
    <?php foreach (['id' => 'ID', 'student_code' => 'Mã sinh viên', 'full_name' => 'Họ tên', 'email' => 'Email', 'phone' => 'Số điện thoại', 'major' => 'Ngành học', 'year' => 'Năm học', 'created_at' => 'Ngày tạo', 'updated_at' => 'Ngày cập nhật'] as $key => $label): ?>
        <div class="card"><span class="meta-label"><?= e($label) ?></span><strong><?= e($student[$key]) ?></strong></div>
    <?php endforeach; ?>
</section>
<section class="card">
    <h2>Điểm 3 môn học</h2>
    <?php if (isset($errors['grades'])): ?><div class="alert alert-danger"><?= e($errors['grades']) ?></div><?php endif; ?>
    <form method="post"><?= csrf_input() ?>
        <table class="table grades-table">
            <thead><tr><th>Mã môn</th><th>Môn học</th><th>Điểm</th></tr></thead>
            <tbody><?php foreach ($grades as $grade): ?><tr><td><?= e($grade['subject_code']) ?></td><td><?= e($grade['subject_name']) ?></td><td><input class="form-control" name="grades[<?= e($grade['subject_code']) ?>]" type="number" min="0" max="10" step="0.01" value="<?= e($grade['score']) ?>" required></td></tr><?php endforeach; ?></tbody>
        </table>
        <div class="form-actions grades-actions"><button class="btn btn-primary" type="submit">Lưu điểm</button></div>
    </form>
</section>
<?php render_footer(); ?>
