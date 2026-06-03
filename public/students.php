<?php

declare(strict_types=1);

// Danh sách sinh viên: tìm kiếm, lọc, mở chi tiết và xóa sinh viên.
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/student.php';

require_role('admin');

if (is_post()) {
    if (!valid_csrf_token()) {
        set_flash('Phiên làm việc không hợp lệ, vui lòng thử lại.');
    } elseif (field_value('action', $_POST) === 'delete') {
        $studentId = (int) ($_POST['id'] ?? 0);
        if ($studentId > 0 && delete_student($studentId)) {
            write_log('INFO', 'STUDENT_DELETED', 'Student deleted', ['student_id' => $studentId]);
        }
    }
    redirect('students.php');
}

$filters = normalize_student_filters($_GET);
$filterErrors = validate_student_filters($filters);
if ($filterErrors !== []) {
    $filters = normalize_student_filters([]);
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$total = count_students($filters);
$students = list_students($filters, $perPage, ($page - 1) * $perPage);
$totalPages = $total === 0 ? 0 : (int) ceil($total / $perPage);

write_log('INFO', 'STUDENT_LIST_VIEW', 'Student list viewed', ['filters' => $filters]);
render_header('Danh sách sinh viên');
?>
<section class="page-header">
    <div><h1>Danh sách sinh viên</h1><p>Tra cứu và quản lý hồ sơ sinh viên.</p></div>
    <a class="btn btn-primary" href="student_form.php">Thêm sinh viên</a>
</section>
<?php render_flash(); ?>
<section class="card">
    <form method="get" class="search-form filter-form">
        <input class="form-control" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Tìm theo mã sinh viên, họ tên hoặc email">
        <select class="form-select" name="major"><option value="">Tất cả ngành học</option><?php foreach (list_student_majors() as $major): ?><option value="<?= e($major) ?>" <?= $filters['major'] === $major ? 'selected' : '' ?>><?= e($major) ?></option><?php endforeach; ?></select>
        <select class="form-select" name="year"><option value="">Tất cả năm học</option><?php foreach (list_student_years() as $year): ?><option value="<?= e($year) ?>" <?= $filters['year'] === (string) $year ? 'selected' : '' ?>><?= e($year) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary" type="submit">Tìm kiếm</button><a class="btn btn-secondary" href="students.php">Làm mới</a>
    </form>
</section>
<section class="table-card">
    <table class="table">
        <thead><tr><th>ID</th><th>Mã sinh viên</th><th>Họ tên</th><th>Email</th><th>Số điện thoại</th><th>Ngành học</th><th>Năm học</th><th>Thao tác</th></tr></thead>
        <tbody>
            <?php if ($students === []): ?><tr><td colspan="8" class="empty-state">Chưa có dữ liệu sinh viên.</td></tr><?php endif; ?>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= e($student['id']) ?></td><td><?= e($student['student_code']) ?></td><td><?= e($student['full_name']) ?></td><td><?= e($student['email']) ?></td><td><?= e($student['phone']) ?></td><td><?= e($student['major']) ?></td><td><?= e($student['year']) ?></td>
                    <td class="actions">
                        <a class="btn btn-primary" href="student_detail.php?id=<?= e($student['id']) ?>">Chi tiết</a>
                        <a class="btn btn-warning" href="student_form.php?id=<?= e($student['id']) ?>">Sửa</a>
                        <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sinh viên này không?')"><?= csrf_input() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($student['id']) ?>"><button class="btn btn-danger" type="submit">Xóa</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php if ($totalPages > 1): ?><nav class="pagination"><?php for ($number = 1; $number <= $totalPages; $number++): ?><a class="<?= $number === $page ? 'active' : '' ?>" href="?<?= e(http_build_query(array_merge($filters, ['page' => $number]))) ?>"><?= e($number) ?></a><?php endfor; ?></nav><?php endif; ?>
<?php render_footer(); ?>
