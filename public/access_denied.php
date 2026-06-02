<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login();

render_header('Không có quyền truy cập');
?>
<section class="card centered-panel">
    <h1>Bạn không có quyền truy cập chức năng này.</h1>
    <p>Vui lòng quay lại trang chính hoặc liên hệ quản trị viên nếu cần thêm quyền.</p>
    <div class="form-actions">
        <a class="btn btn-primary" href="<?= e(landing_page()) ?>">Quay lại</a>
        <?php if (is_admin()): ?>
            <a class="btn btn-secondary" href="students.php">Xem sinh viên</a>
        <?php endif; ?>
    </div>
</section>
<?php render_footer(); ?>
