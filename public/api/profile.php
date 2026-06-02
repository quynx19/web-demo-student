<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/api.php';
require_once __DIR__ . '/../../app/grade.php';
require_once __DIR__ . '/../../app/student.php';
require_once __DIR__ . '/../../app/user.php';

$method = api_request_method();
$action = (string) ($_GET['action'] ?? '');
$allowedMethods = $action === 'password' ? ['PUT', 'OPTIONS'] : ['GET', 'PATCH', 'OPTIONS'];

if ($method === 'OPTIONS') {
    header('Allow: ' . implode(', ', $allowedMethods));
    api_response([], 204);
}

api_require_login();
$userId = (int) $_SESSION['user_id'];
$user = get_user_by_id($userId);

if ($user === null) {
    api_error('User not found.', 404);
}

if ($action === 'password') {
    if ($method !== 'PUT') {
        api_method_not_allowed($allowedMethods);
    }

    api_require_csrf_token();
    $data = api_extract_fields(api_read_json_body(), ['current_password', 'new_password', 'confirm_password']);
    $errors = [];
    $currentPassword = (string) ($data['current_password'] ?? '');
    $newPassword = (string) ($data['new_password'] ?? '');
    $confirmPassword = (string) ($data['confirm_password'] ?? '');

    if (!password_verify($currentPassword, $user['password_hash'])) {
        $errors['current_password'] = 'Mật khẩu hiện tại không đúng.';
    }
    if (strlen($newPassword) < 6) {
        $errors['new_password'] = 'Mật khẩu mới phải tối thiểu 6 ký tự.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Nhập lại mật khẩu mới không khớp.';
    }
    if ($errors !== []) {
        api_error('Validation failed.', 422, $errors);
    }

    change_user_password($userId, $newPassword);
    write_log('INFO', 'API_PASSWORD_CHANGED', 'Password changed through API');
    api_response(['message' => 'Password changed.']);
}

if ($action !== '') {
    api_error('Unknown profile action.', 404);
}

if ($method === 'GET') {
    write_log('INFO', 'API_PROFILE_VIEW', 'Profile API requested');
    $profile = api_public_user($user);
    if ($user['student_id'] !== null) {
        $profile['student'] = get_student((int) $user['student_id']);
        $profile['grades'] = list_student_grades((int) $user['student_id']);
    }
    api_response(['data' => $profile]);
}

if ($method === 'PATCH') {
    api_require_csrf_token();
    $data = api_extract_fields(api_read_json_body(), ['full_name', 'email', 'phone', 'theme']);
    $profile = [
        'full_name' => field_value('full_name', $data, (string) $user['full_name']),
        'email' => field_value('email', $data, (string) $user['email']),
    ];
    $errors = [];

    if ($profile['email'] === '' || !filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không đúng định dạng.';
    }
    if ($errors !== []) {
        api_error('Validation failed.', 422, $errors);
    }

    if ($user['student_id'] !== null) {
        $studentId = (int) $user['student_id'];
        $student = get_student($studentId);
        if ($student === null) {
            api_error('Student not found.', 404);
        }

        $studentProfile = array_merge($student, $profile, [
            'phone' => field_value('phone', $data, (string) $student['phone']),
        ]);
        $errors = validate_student($studentProfile, $studentId);
        if ($errors !== []) {
            api_error('Validation failed.', 422, $errors);
        }

        update_student($studentId, $studentProfile);
    }

    update_current_user_profile($userId, $profile);
    $_SESSION['full_name'] = $profile['full_name'];
    $_SESSION['email'] = $profile['email'];

    if (array_key_exists('theme', $data)) {
        $theme = $data['theme'] === 'dark' ? 'dark' : 'light';
        setcookie('theme', $theme, [
            'expires' => time() + 30 * 24 * 60 * 60,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }

    write_log('INFO', 'API_PROFILE_UPDATED', 'Profile updated through API');
    api_response(['data' => api_public_user(get_user_by_id($userId) ?: [])]);
}

api_method_not_allowed($allowedMethods);
