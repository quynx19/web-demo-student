<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

const API_JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

function api_response(array $payload = [], int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    if ($status !== 204) {
        echo json_encode($payload, API_JSON_FLAGS);
    }

    exit;
}

function api_error(string $message, int $status, array $errors = []): never
{
    $payload = ['error' => $message];

    if ($errors !== []) {
        $payload['errors'] = $errors;
    }

    api_response($payload, $status);
}

function api_request_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

function api_require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    write_log('WARNING', 'PAGE_ACCESS', 'Unauthenticated API access blocked');
    api_error('Authentication required.', 401);
}

function api_require_role(array|string $roles): void
{
    $allowedRoles = is_array($roles) ? $roles : [$roles];

    if (in_array(current_user_role(), $allowedRoles, true)) {
        return;
    }

    write_log('WARNING', 'ACCESS_DENIED', 'API role access denied', [
        'required_roles' => $allowedRoles,
        'current_role' => current_user_role(),
    ]);
    api_error('You do not have permission to perform this action.', 403);
}

function api_require_linked_student_id(): int
{
    $studentId = current_student_id();

    if ($studentId === null) {
        api_error('This account is not linked to a student profile.', 403);
    }

    return $studentId;
}

function api_require_student_access(int $studentId): void
{
    if (is_admin() || api_require_linked_student_id() === $studentId) {
        return;
    }

    api_error('You can only access your own student profile.', 403);
}

function api_require_csrf_token(): void
{
    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (is_string($submittedToken) && hash_equals(csrf_token(), $submittedToken)) {
        return;
    }

    api_error('Invalid CSRF token.', 403);
}

function api_read_json_body(): array
{
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (!str_contains($contentType, 'application/json')) {
        api_error('Content-Type must be application/json.', 415);
    }

    $body = file_get_contents('php://input');
    if ($body === false || trim($body) === '') {
        api_error('JSON request body is required.', 400);
    }

    try {
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        api_error('Request body contains invalid JSON.', 400);
    }

    if (!is_array($data) || array_is_list($data)) {
        api_error('JSON request body must be an object.', 400);
    }

    return $data;
}

function api_extract_fields(array $data, array $allowedFields): array
{
    $unknownFields = array_values(array_diff(array_keys($data), $allowedFields));
    if ($unknownFields !== []) {
        api_error('Request contains unsupported fields.', 422, [
            'fields' => $unknownFields,
        ]);
    }

    return array_intersect_key($data, array_flip($allowedFields));
}

function api_resource_id(): ?int
{
    $pathInfo = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
    $rawId = $pathInfo !== '' ? $pathInfo : ($_GET['id'] ?? null);

    if ($rawId === null || $rawId === '') {
        return null;
    }

    if (!is_scalar($rawId) || !ctype_digit((string) $rawId) || (int) $rawId <= 0) {
        api_error('Resource ID must be a positive integer.', 400);
    }

    return (int) $rawId;
}

function api_positive_query_int(string $name, int $default, int $maximum): int
{
    $rawValue = $_GET[$name] ?? null;

    if ($rawValue === null || $rawValue === '') {
        return $default;
    }

    if (!is_scalar($rawValue) || !ctype_digit((string) $rawValue)) {
        api_error(sprintf('Query parameter "%s" must be a positive integer.', $name), 400);
    }

    $value = (int) $rawValue;
    if ($value <= 0 || $value > $maximum) {
        api_error(sprintf('Query parameter "%s" must be between 1 and %d.', $name, $maximum), 400);
    }

    return $value;
}

function api_require_resource_id(?int $id): int
{
    if ($id === null) {
        api_error('Resource ID is required.', 400);
    }

    return $id;
}

function api_method_not_allowed(array $allowedMethods): never
{
    header('Allow: ' . implode(', ', $allowedMethods));
    api_error('Method not allowed.', 405);
}

function api_public_user(array $user): array
{
    unset($user['password_hash']);

    return $user;
}

set_exception_handler(static function (Throwable $exception): void {
    write_log('ERROR', 'EXCEPTION', 'Unhandled API exception', ['error' => $exception->getMessage()]);
    api_error(generic_error_message(), 500);
});
