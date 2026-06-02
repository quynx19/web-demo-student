<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/api.php';
require_once __DIR__ . '/../../app/user.php';

$method = api_request_method();
$allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

if ($method === 'OPTIONS') {
    header('Allow: ' . implode(', ', $allowedMethods));
    api_response([], 204);
}

api_require_login();
api_require_role('admin');
$id = api_resource_id();

try {
    if ($method === 'GET' && $id === null) {
        $users = array_map('api_public_user', list_users());
        write_log('INFO', 'API_USERS_LIST', 'User API list requested', ['count' => count($users)]);
        api_response(['data' => $users, 'total' => count($users)]);
    }

    if ($method === 'GET') {
        $userId = api_require_resource_id($id);
        $user = get_user_by_id($userId);

        if ($user === null) {
            api_error('User not found.', 404);
        }

        write_log('INFO', 'API_USER_VIEW', 'User API detail requested', ['target_user_id' => $userId]);
        api_response(['data' => api_public_user($user)]);
    }

    if ($method === 'POST') {
        if ($id !== null) {
            api_error('POST requests must target the user collection.', 400);
        }

        api_require_csrf_token();
        $data = api_extract_fields(api_read_json_body(), user_create_fields());
        $errors = validate_user_data($data, true);

        if ($errors !== []) {
            api_error('Validation failed.', 422, $errors);
        }

        $userId = create_user($data);
        write_log('INFO', 'API_USER_CREATED', 'User created through API', ['target_user_id' => $userId]);
        api_response(['data' => api_public_user(get_user_by_id($userId) ?: [])], 201);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        api_require_csrf_token();
        $userId = api_require_resource_id($id);
        $user = get_user_by_id($userId);

        if ($user === null) {
            api_error('User not found.', 404);
        }

        $changes = api_extract_fields(api_read_json_body(), user_update_fields());
        if ($method === 'PATCH' && $changes === []) {
            api_error('PATCH request must contain at least one supported field.', 422);
        }

        $data = $method === 'PATCH' ? array_merge($user, $changes) : array_merge(['username' => $user['username']], $changes);
        if ($userId === (int) $_SESSION['user_id'] && field_value('status', $data) === 'locked') {
            api_error('Current user cannot lock their own account.', 422, ['status' => 'Không thể tự khóa tài khoản đang đăng nhập.']);
        }

        $errors = validate_user_data($data, false, $userId);
        if ($errors !== []) {
            api_error('Validation failed.', 422, $errors);
        }

        update_user($userId, $data);
        write_log('INFO', 'API_USER_UPDATED', 'User updated through API', ['target_user_id' => $userId]);
        api_response(['data' => api_public_user(get_user_by_id($userId) ?: [])]);
    }

    if ($method === 'DELETE') {
        api_require_csrf_token();
        $userId = api_require_resource_id($id);

        if ($userId === (int) $_SESSION['user_id']) {
            api_error('Current user cannot delete their own account.', 422);
        }
        if (!delete_user($userId)) {
            api_error('User not found.', 404);
        }

        write_log('INFO', 'API_USER_DELETED', 'User deleted through API', ['target_user_id' => $userId]);
        api_response([], 204);
    }

    api_method_not_allowed($allowedMethods);
} catch (PDOException $exception) {
    write_log('ERROR', 'EXCEPTION', 'User API database operation failed', ['error' => $exception->getMessage()]);

    if ($exception->getCode() === '23000') {
        api_error('Username already exists.', 409);
    }

    api_error(generic_error_message(), 500);
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'User API failed', ['error' => $exception->getMessage()]);
    api_error(generic_error_message(), 500);
}
