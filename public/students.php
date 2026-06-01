<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/student.php';

require_login();

$filters = [
    'q' => field_value('q', $_GET),
    'major' => field_value('major', $_GET),
    'year' => field_value('year', $_GET),
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page - 1) * $perPage;
$students = [];
$total = 0;
$majors = [];
$years = [];
$error = '';

try {
    $students = list_students($filters, $perPage, $offset);
    $total = count_students($filters);
    $majors = list_student_majors();
    $years = list_student_years();
    $hasFilter = implode('', $filters) !== '';
    write_log('INFO', $hasFilter ? 'STUDENT_SEARCH' : 'STUDENT_LIST_VIEW', 'Student list viewed', [
        'filters' => $filters,
        'count' => count($students),
    ]);
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'Failed to load student list', ['error' => $exception->getMessage()]);
    $error = generic_error_message();
}

render_header('Danh sách sinh viên');
?>
<section class="page-header">
    <div>
        <h1>Danh sách sinh viên</h1>
        <p>Quản lý, tìm kiếm và theo dõi hồ sơ sinh viên trong hệ thống.</p>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn-primary" href="student_add.php">Thêm sinh viên</a>
    <?php endif; ?>
</section>

<?php render_flash(); ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<section class="card">
    <form method="get" class="search-form filter-form">
        <input class="form-control" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Tìm theo mã sinh viên, họ tên hoặc email">
        <select class="form-select" name="major">
            <option value="">Tất cả ngành học</option>
            <?php foreach ($majors as $major): ?>
                <option value="<?= e($major) ?>" <?= $filters['major'] === (string) $major ? 'selected' : '' ?>><?= e($major) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select" name="year">
            <option value="">Tất cả năm học</option>
            <?php foreach ($years as $year): ?>
                <option value="<?= e($year) ?>" <?= $filters['year'] === (string) $year ? 'selected' : '' ?>>Năm <?= e($year) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Tìm kiếm</button>
        <?php if (implode('', $filters) !== ''): ?>
            <a class="btn btn-secondary" href="students.php">Làm mới</a>
        <?php endif; ?>
    </form>
</section>

<section class="table-card">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã sinh viên</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Ngành học</th>
                <th>Năm học</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($students === []): ?>
                <tr>
                    <td colspan="8" class="empty-state">Chưa có dữ liệu sinh viên.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= e($student['id']) ?></td>
                    <td><?= e($student['student_code']) ?></td>
                    <td><?= e($student['full_name']) ?></td>
                    <td><?= e($student['email']) ?></td>
                    <td><?= e($student['phone']) ?></td>
                    <td><?= e($student['major']) ?></td>
                    <td><?= e($student['year']) ?></td>
                    <td class="actions">
                        <a class="btn btn-primary" href="student_detail.php?id=<?= e($student['id']) ?>">Chi tiết</a>
                        <?php if (is_admin()): ?>
                            <a class="btn btn-warning" href="student_edit.php?id=<?= e($student['id']) ?>">Sửa</a>
                            <a class="btn btn-danger" href="student_delete.php?id=<?= e($student['id']) ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa sinh viên này không?');">Xóa</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php $totalPages = max(1, (int) ceil($total / $perPage)); ?>
<?php if ($totalPages > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php $query = array_merge($filters, ['page' => $i]); ?>
            <a class="<?= $i === $page ? 'active' : '' ?>" href="students.php?<?= e(http_build_query($query)) ?>"><?= e($i) ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
<?php render_footer(); ?>
