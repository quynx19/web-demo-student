<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/api.php';
require_once __DIR__ . '/../../app/user.php';

$method = api_request_method();
$allowedMethods = ['GET', 'POST', 'DELETE', 'OPTIONS'];

if ($method === 'OPTIONS') {
    header('Allow: ' . implode(', ', $allowedMethods));
    api_response([], 204);
}

if ($method === 'GET') {
    $user = is_logged_in() ? get_user_by_id((int) $_SESSION['user_id']) : null;
    api_response([
        'authenticated' => $user !== null,
        'csrf_token' => csrf_token(),
        'data' => $user === null ? null : api_public_user($user),
    ]);
}

if ($method === 'POST') {
    api_require_csrf_token();
    $data = api_extract_fields(api_read_json_body(), ['username', 'password']);
    $username = field_value('username', $data);
    $password = (string) ($data['password'] ?? '');

    if ($username === '' || $password === '' || !login_user($username, $password)) {
        api_error('Invalid username or password.', 401);
    }

    $user = get_user_by_id((int) $_SESSION['user_id']);
    api_response([
        'csrf_token' => csrf_token(),
        'data' => api_public_user($user ?: []),
    ]);
}

if ($method === 'DELETE') {
    api_require_login();
    api_require_csrf_token();
    logout_current_user();
    api_response([], 204);
}

api_method_not_allowed($allowedMethods);
