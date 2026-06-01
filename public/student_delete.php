<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/student.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('error', 'Sinh viên không hợp lệ.');
    redirect('students.php');
}

try {
    delete_student($id);
    write_log('INFO', 'STUDENT_DELETED', 'Student deleted', ['student_id' => $id]);
    set_flash('success', 'Đã xóa sinh viên thành công.');
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'Failed to delete student', ['error' => $exception->getMessage(), 'student_id' => $id]);
    set_flash('error', generic_error_message());
}

redirect('students.php');
