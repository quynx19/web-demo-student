<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_role('admin');

render_header('Nhật ký ứng dụng');
?>
<section class="page-header">
    <div>
        <h1>Nhật ký hoạt động ứng dụng</h1>
        <p>Dữ liệu log được tải từ REST API.</p>
    </div>
</section>
<section class="card">
    <form class="search-form" data-logs-filter>
        <input class="form-control" name="keyword" placeholder="Lọc từ khóa: LOGIN_SUCCESS, ACCESS_DENIED, API_STUDENT_CREATED">
        <button class="btn btn-primary" type="submit">Lọc log</button>
        <a class="btn btn-secondary" href="logs.php">Làm mới</a>
    </form>
</section>
<section class="table-card log-table" data-logs-list>
    <div class="log-scrollbar-top" data-log-scrollbar-top tabindex="0" aria-label="Cuộn ngang nội dung log">
        <div class="log-scrollbar-top-content" data-log-scrollbar-top-content></div>
    </div>
    <div class="log-table-scroll" data-log-table-scroll>
        <table class="table">
            <thead><tr><th>#</th><th>Nội dung log</th></tr></thead>
            <tbody><tr><td colspan="2" class="empty-state">Đang tải dữ liệu...</td></tr></tbody>
        </table>
    </div>
</section>
<script>
(() => {
    const topScrollbar = document.querySelector('[data-log-scrollbar-top]');
    const topScrollbarContent = document.querySelector('[data-log-scrollbar-top-content]');
    const tableScroll = document.querySelector('[data-log-table-scroll]');
    const table = tableScroll?.querySelector('table');

    if (!topScrollbar || !topScrollbarContent || !tableScroll || !table) {
        return;
    }

    const syncScrollbarWidth = () => {
        topScrollbarContent.style.width = `${table.scrollWidth}px`;
    };

    topScrollbar.addEventListener('scroll', () => {
        tableScroll.scrollLeft = topScrollbar.scrollLeft;
    });
    tableScroll.addEventListener('scroll', () => {
        topScrollbar.scrollLeft = tableScroll.scrollLeft;
    });
    window.addEventListener('resize', syncScrollbarWidth);
    window.addEventListener('app:logs-updated', syncScrollbarWidth);
    syncScrollbarWidth();
})();
</script>
<?php render_footer(); ?>
