<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$username = '';

if (is_post()) {
    $username = field_value('username', $_POST);
    $password = (string) ($_POST['password'] ?? '');

    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT status FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $account = $stmt->fetch();

        if ($account && $account['status'] === 'locked') {
            write_log('WARNING', 'LOGIN_FAILED', 'Locked account login attempt', ['username' => $username]);
            $error = 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.';
        } elseif (login_user($username, $password)) {
            redirect('index.php');
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    } catch (Throwable $exception) {
        write_log('ERROR', 'EXCEPTION', 'Login failed with exception', ['error' => $exception->getMessage()]);
        $error = generic_error_message();
    }
}

render_header('Đăng nhập hệ thống');
?>
<section class="login-card">
    <h1>Đăng nhập hệ thống</h1>
    <p>Hệ thống quản lý hồ sơ sinh viên</p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="form">
        <div>
            <label for="username">Tên đăng nhập</label>
            <input class="form-control" id="username" name="username" type="text" value="<?= e($username) ?>" autocomplete="username" required>
        </div>

        <div>
            <label for="password">Mật khẩu</label>
            <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>

        <button class="btn btn-primary button-full" type="submit">Đăng nhập</button>
    </form>
    <div class="login-footer">Demo Web Application Logging</div>
</section>
<?php render_footer(); ?>
