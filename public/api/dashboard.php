<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/api.php';
require_once __DIR__ . '/../../app/student.php';
require_once __DIR__ . '/../../app/user.php';

$method = api_request_method();

if ($method === 'OPTIONS') {
    header('Allow: GET, OPTIONS');
    api_response([], 204);
}
if ($method !== 'GET') {
    api_method_not_allowed(['GET', 'OPTIONS']);
}

api_require_login();
$payload = [
    'student_count' => count_students(),
    'major_stats' => count_students_by_major(),
    'recent_students' => list_students([], 5, 0),
];
$payload['major_count'] = count($payload['major_stats']);

if (is_admin()) {
    $logFile = __DIR__ . '/../../logs/app.log';
    $payload['user_count'] = count_users();
    $payload['recent_log_count'] = is_file($logFile)
        ? count(array_slice(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [], -100))
        : 0;
}

write_log('INFO', 'API_DASHBOARD_VIEW', 'Dashboard API requested');
api_response(['data' => $payload]);
