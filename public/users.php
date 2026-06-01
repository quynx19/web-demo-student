<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/user.php';

require_role('admin');

try {
    $users = list_users();
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'Failed to list users', ['error' => $exception->getMessage()]);
    $users = [];
    $error = generic_error_message();
}

render_header('Quản lý tài khoản');
?>
<section class="page-header">
    <div>
        <h1>Quản lý tài khoản</h1>
        <p>Quản lý người dùng, vai trò và trạng thái tài khoản.</p>
    </div>
    <a class="btn btn-primary" href="user_add.php">Thêm tài khoản</a>
</section>

<?php render_flash(); ?>
<?php if (isset($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<section class="table-card">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['id']) ?></td>
                    <td><?= e($user['username']) ?></td>
                    <td><?= e($user['full_name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><span class="badge <?= $user['role'] === 'user' ? 'badge-user' : 'badge-admin' ?>"><?= e(role_label($user['role'])) ?></span></td>
                    <td><?= e($user['status'] === 'active' ? 'Đang hoạt động' : 'Đã khóa') ?></td>
                    <td class="actions">
                        <a class="btn btn-warning" href="user_edit.php?id=<?= e($user['id']) ?>">Sửa</a>
                        <?php if ((int) $user['id'] !== (int) $_SESSION['user_id']): ?>
                            <?php if ($user['status'] === 'active'): ?>
                                <a class="btn btn-danger" href="user_lock.php?id=<?= e($user['id']) ?>" onclick="return confirm('Bạn có chắc chắn muốn khóa tài khoản này không?');">Khóa</a>
                            <?php else: ?>
                                <a class="btn btn-primary" href="user_unlock.php?id=<?= e($user['id']) ?>">Mở khóa</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
