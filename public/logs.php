<?php

declare(strict_types=1);

// Nhật ký ứng dụng: hiển thị và lọc các dòng log thao tác trong logs/app.log.
require_once __DIR__ . '/../app/auth.php';

require_role('admin');

$keyword = field_value('keyword', $_GET);
$logFile = __DIR__ . '/../logs/app.log';
$lines = is_file($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] : [];
if ($keyword !== '') {
    $lines = array_values(array_filter($lines, fn (string $line): bool => stripos($line, $keyword) !== false));
}
$lines = array_slice(array_reverse($lines), 0, 100);

write_log('INFO', 'LOG_VIEW', 'Application log viewed', ['keyword' => $keyword]);
render_header('Nhật ký ứng dụng');
?>
<section class="page-header"><div><h1>Nhật ký hoạt động ứng dụng</h1><p>Theo dõi các hoạt động gần đây của hệ thống.</p></div></section>
<section class="card">
    <form method="get" class="search-form">
        <input class="form-control" name="keyword" value="<?= e($keyword) ?>" placeholder="Lọc từ khóa: LOGIN_SUCCESS, ACCESS_DENIED, STUDENT_CREATED">
        <button class="btn btn-primary" type="submit">Lọc log</button>
        <a class="btn btn-secondary" href="logs.php">Làm mới</a>
    </form>
</section>
<section class="table-card log-table">
    <div class="log-scrollbar-top" data-log-scrollbar-top tabindex="0" aria-label="Cuộn ngang nội dung log"><div class="log-scrollbar-top-content" data-log-scrollbar-top-content></div></div>
    <div class="log-table-scroll" data-log-table-scroll>
        <table class="table">
            <thead><tr><th>#</th><th>Nội dung log</th></tr></thead>
            <tbody>
                <?php if ($lines === []): ?><tr><td colspan="2" class="empty-state">Chưa có log phù hợp.</td></tr><?php endif; ?>
                <?php foreach ($lines as $index => $line): ?><tr><td><?= e($index + 1) ?></td><td><code><?= e($line) ?></code></td></tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<script>
(() => {
    const top = document.querySelector('[data-log-scrollbar-top]');
    const content = document.querySelector('[data-log-scrollbar-top-content]');
    const scroll = document.querySelector('[data-log-table-scroll]');
    const table = scroll?.querySelector('table');
    if (!top || !content || !scroll || !table) return;
    const resize = () => { content.style.width = `${table.scrollWidth}px`; };
    top.addEventListener('scroll', () => { scroll.scrollLeft = top.scrollLeft; });
    scroll.addEventListener('scroll', () => { top.scrollLeft = scroll.scrollLeft; });
    window.addEventListener('resize', resize);
    resize();
})();
</script>
<?php render_footer(); ?>
