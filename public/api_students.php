<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/student.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    write_log('WARNING', 'PAGE_ACCESS', 'Unauthenticated API access blocked');
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập'], JSON_UNESCAPED_UNICODE);
    exit;
}

$filters = [
    'q' => field_value('q', $_GET),
    'major' => field_value('major', $_GET),
    'year' => field_value('year', $_GET),
];

try {
    $students = list_students($filters);
    write_log('INFO', 'API_STUDENTS_REQUEST', 'Student API requested', [
        'filters' => $filters,
        'count' => count($students),
    ]);

    echo json_encode([
        'data' => $students,
        'total' => count($students),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'Student API failed', ['error' => $exception->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => generic_error_message()], JSON_UNESCAPED_UNICODE);
}
