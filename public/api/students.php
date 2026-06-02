<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/api.php';
require_once __DIR__ . '/../../app/grade.php';
require_once __DIR__ . '/../../app/student.php';

$allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
$method = api_request_method();

if ($method === 'OPTIONS') {
    header('Allow: ' . implode(', ', $allowedMethods));
    api_response([], 204);
}

api_require_login();
$id = api_resource_id();

try {
    if ($method === 'GET' && $id === null) {
        $filters = normalize_student_filters($_GET);
        if (!is_admin()) {
            $filters['student_id'] = api_require_linked_student_id();
        }
        $filterErrors = validate_student_filters($filters);
        if ($filterErrors !== []) {
            api_error('Invalid filters.', 422, $filterErrors);
        }

        $page = api_positive_query_int('page', 1, 1000000);
        $perPage = api_positive_query_int('per_page', 20, 100);
        $offset = ($page - 1) * $perPage;
        $students = list_students($filters, $perPage, $offset);
        $total = count_students($filters);

        write_log('INFO', 'API_STUDENTS_LIST', 'Student API list requested', [
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'count' => count($students),
            'total' => $total,
        ]);

        $filterOptions = is_admin()
            ? ['majors' => list_student_majors(), 'years' => list_student_years()]
            : [
                'majors' => array_values(array_unique(array_filter(array_column($students, 'major')))),
                'years' => array_values(array_unique(array_filter(array_column($students, 'year')))),
            ];

        api_response([
            'data' => $students,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total === 0 ? 0 : (int) ceil($total / $perPage),
                'filter_options' => $filterOptions,
            ],
        ]);
    }

    if ($method === 'GET') {
        $studentId = api_require_resource_id($id);
        api_require_student_access($studentId);
        $student = get_student($studentId);

        if ($student === null) {
            api_error('Student not found.', 404);
        }

        write_log('INFO', 'API_STUDENT_VIEW', 'Student API detail requested', ['student_id' => $studentId]);
        api_response(['data' => $student]);
    }

    if ($method === 'POST') {
        if ($id !== null) {
            api_error('POST requests must target the student collection.', 400);
        }

        api_require_role('admin');
        api_require_csrf_token();
        $data = api_extract_fields(api_read_json_body(), student_writable_fields());
        $errors = validate_student($data);

        if ($errors !== []) {
            api_error('Validation failed.', 422, $errors);
        }

        $studentId = create_student($data);
        initialize_student_grades($studentId);
        $student = get_student($studentId);
        header('Location: students/' . $studentId);
        write_log('INFO', 'API_STUDENT_CREATED', 'Student created through API', ['student_id' => $studentId]);
        api_response(['data' => $student], 201);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        api_require_role('admin');
        api_require_csrf_token();
        $studentId = api_require_resource_id($id);
        $student = get_student($studentId);

        if ($student === null) {
            api_error('Student not found.', 404);
        }

        $changes = api_extract_fields(api_read_json_body(), student_writable_fields());
        if ($method === 'PATCH' && $changes === []) {
            api_error('PATCH request must contain at least one supported field.', 422);
        }

        $data = $method === 'PATCH' ? array_merge($student, $changes) : $changes;
        $errors = validate_student($data, $studentId);

        if ($errors !== []) {
            api_error('Validation failed.', 422, $errors);
        }

        update_student($studentId, $data);
        write_log('INFO', 'API_STUDENT_UPDATED', 'Student updated through API', [
            'student_id' => $studentId,
            'method' => $method,
        ]);
        api_response(['data' => get_student($studentId)]);
    }

    if ($method === 'DELETE') {
        api_require_role('admin');
        api_require_csrf_token();
        $studentId = api_require_resource_id($id);

        if (!delete_student($studentId)) {
            api_error('Student not found.', 404);
        }

        write_log('INFO', 'API_STUDENT_DELETED', 'Student deleted through API', ['student_id' => $studentId]);
        api_response([], 204);
    }

    api_method_not_allowed($allowedMethods);
} catch (PDOException $exception) {
    write_log('ERROR', 'EXCEPTION', 'Student API database operation failed', ['error' => $exception->getMessage()]);

    if ($exception->getCode() === '23000') {
        api_error('Student code already exists.', 409);
    }

    api_error(generic_error_message(), 500);
} catch (Throwable $exception) {
    write_log('ERROR', 'EXCEPTION', 'Student API failed', ['error' => $exception->getMessage()]);
    api_error(generic_error_message(), 500);
}
