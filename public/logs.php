<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_role('admin');

$keyword = field_value('keyword', $_GET);
$logFile = __DIR__ . '/../logs/app.log';
$lines = [];

if (is_file($logFile)) {
    $allLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    if ($keyword !== '') {
        $allLines = array_values(array_filter($allLines, fn (string $line): bool => stripos($line, $keyword) !== false));
    }
    $lines = array_slice($allLines, -100);
}

write_log('INFO', 'LOG_VIEW', 'Application log viewed', ['keyword' => $keyword, 'count' => count($lines)]);

render_header('Nhật ký ứng dụng');
?>
<section class="page-header">
    <div>
        <h1>Nhật ký hoạt động ứng dụng</h1>
        <p>Theo dõi các sự kiện đăng nhập, truy cập, thao tác dữ liệu và lỗi phát sinh.</p>
    </div>
</section>

<section class="card">
    <form method="get" class="search-form">
        <input class="form-control" name="keyword" value="<?= e($keyword) ?>" placeholder="Lọc từ khóa: LOGIN_SUCCESS, ACCESS_DENIED, STUDENT_CREATED">
        <button class="btn btn-primary" type="submit">Lọc log</button>
        <?php if ($keyword !== ''): ?><a class="btn btn-secondary" href="logs.php">Làm mới</a><?php endif; ?>
    </form>
</section>

<section class="table-card log-table">
    <table class="table">
        <thead><tr><th>#</th><th>Nội dung log</th></tr></thead>
        <tbody>
            <?php foreach (array_reverse($lines) as $index => $line): ?>
                <tr><td><?= e($index + 1) ?></td><td><code><?= e($line) ?></code></td></tr>
            <?php endforeach; ?>
            <?php if ($lines === []): ?><tr><td colspan="2" class="empty-state">Chưa có log phù hợp.</td></tr><?php endif; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
