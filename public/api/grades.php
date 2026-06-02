<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/api.php';
require_once __DIR__ . '/../../app/grade.php';
require_once __DIR__ . '/../../app/student.php';

$allowedMethods = ['GET', 'PUT', 'OPTIONS'];
$method = api_request_method();

if ($method === 'OPTIONS') {
    header('Allow: ' . implode(', ', $allowedMethods));
    api_response([], 204);
}

api_require_login();
$rawStudentId = $_GET['student_id'] ?? null;
if (!is_scalar($rawStudentId) || !ctype_digit((string) $rawStudentId) || (int) $rawStudentId <= 0) {
    api_error('Student ID must be a positive integer.', 400);
}

$studentId = (int) $rawStudentId;
api_require_student_access($studentId);

if (get_student($studentId) === null) {
    api_error('Student not found.', 404);
}

try {
    if ($method === 'GET') {
        write_log('INFO', 'API_STUDENT_GRADES_VIEW', 'Student grades requested', ['student_id' => $studentId]);
        api_response(['data' => list_student_grades($studentId)]);
    }

    if ($method === 'PUT') {
        api_require_role('admin');
        api_require_csrf_token();
        $data = api_extract_fields(api_read_json_body(), ['grades']);
        $errors = validate_student_grades($data);

        if ($errors !== []) {
            api_error('Validation failed.', 422, $errors);
        }

        replace_student_grades($studentId, $data);
        write_log('INFO', 'API_STUDENT_GRADES_UPDATED', 'Student grades updated', ['student_id' => $studentId]);
        api_response(['data' => list_student_grades($studentId)]);
    }

    api_method_not_allowed($allowedMethods);
} catch (PDOException $exception) {
    write_log('ERROR', 'EXCEPTION', 'Student grade API database operation failed', ['error' => $exception->getMessage()]);
    api_error(generic_error_message(), 500);
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'Student grade API failed', ['error' => $exception->getMessage()]);
    api_error(generic_error_message(), 500);
}
