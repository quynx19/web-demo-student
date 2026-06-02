<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login();
if (!is_admin()) {
    redirect('profile.php');
}

$filters = [
    'q' => field_value('q', $_GET),
    'major' => field_value('major', $_GET),
    'year' => field_value('year', $_GET),
];

render_header('Danh sách sinh viên');
?>
<section class="page-header">
    <div>
        <h1>Danh sách sinh viên</h1>
        <p>Tra cứu và quản lý hồ sơ sinh viên.</p>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn-primary" href="student_form.php">Thêm sinh viên</a>
    <?php endif; ?>
</section>

<?php render_flash(); ?>

<section class="card">
    <form method="get" class="search-form filter-form" data-students-filter>
        <input class="form-control" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Tìm theo mã sinh viên, họ tên hoặc email">
        <select class="form-select" name="major">
            <option value="<?= e($filters['major']) ?>"><?= e($filters['major'] !== '' ? $filters['major'] : 'Tất cả ngành học') ?></option>
        </select>
        <select class="form-select" name="year">
            <option value="<?= e($filters['year']) ?>"><?= e($filters['year'] !== '' ? $filters['year'] : 'Tất cả năm học') ?></option>
        </select>
        <button class="btn btn-primary" type="submit">Tìm kiếm</button>
        <a class="btn btn-secondary" href="students.php">Làm mới</a>
    </form>
</section>

<section class="table-card" data-students-list data-admin="<?= is_admin() ? '1' : '0' ?>">
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
        <tbody><tr><td colspan="8" class="empty-state">Đang tải dữ liệu...</td></tr></tbody>
    </table>
</section>
<nav class="pagination" data-students-pagination></nav>
<?php render_footer(); ?>
