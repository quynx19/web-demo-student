<?php

declare(strict_types=1);

// Form sinh viên: dùng chung cho thêm mới và chỉnh sửa thông tin sinh viên.
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/grade.php';
require_once __DIR__ . '/../app/student.php';
require_once __DIR__ . '/../app/user.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$student = $isEdit ? get_student($id) : null;
if ($isEdit && $student === null) {
    set_flash('Không tìm thấy sinh viên.');
    redirect('students.php');
}

$form = $student ?? ['student_code' => '', 'full_name' => '', 'email' => '', 'phone' => '', 'major' => '', 'year' => ''];
$accountForm = normalize_student_account_data([]);
$errors = [];
if (is_post()) {
    $form = normalize_student_data($_POST);
    $accountForm = normalize_student_account_data($_POST);

    if (!valid_csrf_token()) {
        $errors['form'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } else {
        $errors = validate_student($form, $isEdit ? $id : null);

        if (!$isEdit) {
            $errors = array_merge($errors, validate_student_account_data($accountForm));
        }
    }

    if ($errors === []) {
        $pdo = get_pdo();

        if ($isEdit) {
            $pdo->beginTransaction();

            try {
                update_student($id, $form);
                update_linked_student_account($id, $form);
                $pdo->commit();

                write_log('INFO', 'STUDENT_UPDATED', 'Student updated', ['student_id' => $id]);
                redirect('students.php');
            } catch (Throwable $exception) {
                $pdo->rollBack();
                $errors['form'] = 'Không thể cập nhật sinh viên và tài khoản liên kết, vui lòng thử lại.';
            }
        } else {
            $pdo->beginTransaction();

            try {
                $id = create_student($form);
                initialize_student_grades($id);
                $userId = create_user([
                    'student_id' => (string) $id,
                    'username' => $accountForm['account_username'],
                    'password' => $accountForm['account_password'],
                    'full_name' => $form['full_name'],
                    'email' => $form['email'],
                    'role' => 'user',
                    'status' => $accountForm['account_status'],
                ]);
                $pdo->commit();

                write_log('INFO', 'STUDENT_CREATED', 'Student created', ['student_id' => $id]);
                write_log('INFO', 'USER_CREATED', 'User created with student', ['target_user_id' => $userId, 'student_id' => $id]);
                redirect('students.php');
            } catch (Throwable $exception) {
                $pdo->rollBack();
                $errors['form'] = 'Không thể tạo sinh viên và tài khoản, vui lòng thử lại.';
            }
        }
    }
}

render_header($isEdit ? 'Sửa sinh viên' : 'Thêm sinh viên');
?>
<section class="page-header"><div><h1><?= $isEdit ? 'Sửa sinh viên' : 'Thêm sinh viên' ?></h1><p><?= $isEdit ? 'Chỉnh sửa thông tin hồ sơ sinh viên.' : 'Nhập thông tin để tạo hồ sơ sinh viên mới.' ?></p></div><a class="btn btn-secondary" href="students.php">Quay lại</a></section>
<section class="card">
    <?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
    <form method="post" class="form form-grid">
        <?= csrf_input() ?>
        <?php render_student_form_fields($form, $errors); ?>
        <?php if (!$isEdit): ?>
            <h2 class="form-section-title">Tài khoản đăng nhập</h2>
            <div>
                <label for="account_username">Tên đăng nhập</label>
                <input class="form-control" id="account_username" name="account_username" value="<?= e($accountForm['account_username']) ?>" required>
                <?php if (isset($errors['account_username'])): ?><span class="field-error"><?= e($errors['account_username']) ?></span><?php endif; ?>
            </div>
            <div>
                <label for="account_password">Mật khẩu ban đầu</label>
                <input class="form-control" id="account_password" name="account_password" type="password" required>
                <?php if (isset($errors['account_password'])): ?><span class="field-error"><?= e($errors['account_password']) ?></span><?php endif; ?>
            </div>
            <div>
                <label for="account_status">Trạng thái tài khoản</label>
                <select class="form-select" id="account_status" name="account_status">
                    <option value="active" <?= $accountForm['account_status'] === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                    <option value="locked" <?= $accountForm['account_status'] === 'locked' ? 'selected' : '' ?>>Đã khóa</option>
                </select>
                <?php if (isset($errors['account_status'])): ?><span class="field-error"><?= e($errors['account_status']) ?></span><?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= $isEdit ? 'Cập nhật' : 'Lưu sinh viên' ?></button><a class="btn btn-secondary" href="students.php">Hủy</a></div>
    </form>
</section>
<?php render_footer(); ?>
