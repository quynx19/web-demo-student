<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login();

render_header('Tổng quan');
?>
<div data-dashboard>
    <section class="page-header">
        <div>
            <h1>Xin chào, <?= e($_SESSION['username'] ?? '') ?></h1>
            <p>Dữ liệu tổng quan được tải từ REST API.</p>
        </div>
        <a class="btn btn-primary" href="students.php">Xem danh sách sinh viên</a>
    </section>

    <section class="stats-grid">
        <article class="stat-card"><span class="stat-label">Tổng số sinh viên</span><strong data-dashboard-value="student_count">...</strong></article>
        <article class="stat-card"><span class="stat-label">Số ngành học</span><strong data-dashboard-value="major_count">...</strong></article>
        <?php if (is_admin()): ?>
            <article class="stat-card"><span class="stat-label">Tổng số tài khoản</span><strong data-dashboard-value="user_count">...</strong></article>
            <article class="stat-card"><span class="stat-label">Log gần đây</span><strong data-dashboard-value="recent_log_count">...</strong></article>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Thao tác nhanh</h2>
        <div class="quick-links">
            <a href="students.php">Xem danh sách sinh viên</a>
            <?php if (is_admin()): ?>
                <a href="student_add.php">Thêm sinh viên</a>
                <a href="users.php">Quản lý tài khoản</a>
                <a href="logs.php">Xem nhật ký ứng dụng</a>
            <?php endif; ?>
            <a href="profile.php">Hồ sơ cá nhân</a>
        </div>
    </section>

    <section class="card">
        <h2>Sinh viên theo ngành học</h2>
        <div class="mini-table" data-dashboard-majors><div>Đang tải dữ liệu...</div></div>
    </section>

    <section class="table-card">
        <div class="table-title"><h2>Sinh viên mới cập nhật gần đây</h2></div>
        <table class="table">
            <thead><tr><th>Mã sinh viên</th><th>Họ tên</th><th>Email</th><th>Ngành học</th><th>Năm học</th></tr></thead>
            <tbody data-dashboard-students><tr><td colspan="5" class="empty-state">Đang tải dữ liệu...</td></tr></tbody>
        </table>
    </section>
</div>
<?php render_footer(); ?>
