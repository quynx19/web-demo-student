<?php

declare(strict_types=1);

// Quản lý tài khoản: danh sách, khóa/mở khóa, xóa và mở form chỉnh sửa user.
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/user.php';

require_role('admin');

if (is_post()) {
    if (!valid_csrf_token()) {
        set_flash('Phiên làm việc không hợp lệ, vui lòng thử lại.');
        redirect('users.php');
    }

    $userId = (int) ($_POST['id'] ?? 0);
    $action = field_value('action', $_POST);
    $user = $userId > 0 ? get_user_by_id($userId) : null;
    if ($user === null) {
        set_flash('Không tìm thấy tài khoản.');
        redirect('users.php');
    }

    if ($userId === (int) $_SESSION['user_id']) {
        set_flash('Không thể thay đổi trạng thái hoặc xóa tài khoản đang đăng nhập.');
        redirect('users.php');
    }

    if ($action === 'delete') {
        delete_user($userId);
        write_log('INFO', 'USER_DELETED', 'User deleted', ['target_user_id' => $userId]);
    } elseif ($action === 'status') {
        $user['status'] = $user['status'] === 'active' ? 'locked' : 'active';
        update_user($userId, $user);
        write_log('INFO', 'USER_STATUS_UPDATED', 'User status updated', ['target_user_id' => $userId, 'status' => $user['status']]);
    }

    redirect('users.php');
}

$users = list_users();
write_log('INFO', 'USER_LIST_VIEW', 'User list viewed');
render_header('Quản lý tài khoản');
?>
<section class="page-header">
    <div><h1>Quản lý tài khoản</h1><p>Quản lý trạng thái tài khoản. Sinh viên mới nên được tạo từ chức năng Thêm sinh viên để tự động liên kết tài khoản.</p></div>
    <a class="btn btn-primary" href="user_form.php">Thêm tài khoản</a>
</section>
<?php render_flash(); ?>
<section class="table-card">
    <table class="table">
        <thead><tr><th>ID</th><th>Tên đăng nhập</th><th>Họ tên</th><th>Email</th><th>Sinh viên liên kết</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['id']) ?></td><td><?= e($user['username']) ?></td><td><?= e($user['full_name']) ?></td><td><?= e($user['email']) ?></td><td><?= e($user['student_code']) ?></td><td><?= e($user['role']) ?></td><td><?= e($user['status']) ?></td>
                    <td class="actions">
                        <a class="btn btn-warning" href="user_form.php?id=<?= e($user['id']) ?>">Sửa</a>
                        <?php if ((int) $user['id'] !== (int) $_SESSION['user_id']): ?>
                            <form method="post"><?= csrf_input() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= e($user['id']) ?>"><button class="btn <?= $user['status'] === 'active' ? 'btn-danger' : 'btn-primary' ?>" type="submit"><?= $user['status'] === 'active' ? 'Khóa' : 'Mở khóa' ?></button></form>
                            <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản này không?')"><?= csrf_input() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($user['id']) ?>"><button class="btn btn-danger" type="submit">Xóa</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
