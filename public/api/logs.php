<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/api.php';

$method = api_request_method();

if ($method === 'OPTIONS') {
    header('Allow: GET, OPTIONS');
    api_response([], 204);
}
if ($method !== 'GET') {
    api_method_not_allowed(['GET', 'OPTIONS']);
}

api_require_login();
api_require_role('admin');
$keyword = field_value('keyword', $_GET);
$page = api_positive_query_int('page', 1, 1000000);
$perPage = api_positive_query_int('per_page', 100, 100);
$logFile = __DIR__ . '/../../logs/app.log';
$lines = [];

if (is_file($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    if ($keyword !== '') {
        $lines = array_values(array_filter($lines, fn (string $line): bool => stripos($line, $keyword) !== false));
    }
}

$lines = array_reverse($lines);
$total = count($lines);
$offset = ($page - 1) * $perPage;
$lines = array_slice($lines, $offset, $perPage);

write_log('INFO', 'API_LOG_VIEW', 'Application log API requested', [
    'keyword' => $keyword,
    'count' => count($lines),
]);
api_response([
    'data' => $lines,
    'meta' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $total === 0 ? 0 : (int) ceil($total / $perPage),
    ],
]);
