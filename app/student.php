<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

function validate_student(array $data): array
{
    $errors = [];

    if (field_value('student_code', $data) === '') {
        $errors['student_code'] = 'Mã sinh viên không được để trống.';
    }

    if (field_value('full_name', $data) === '') {
        $errors['full_name'] = 'Họ tên không được để trống.';
    }

    $email = field_value('email', $data);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không đúng định dạng.';
    }

    return $errors;
}

function normalize_student_filters(array $filters): array
{
    return [
        'q' => trim((string) ($filters['q'] ?? '')),
        'major' => trim((string) ($filters['major'] ?? '')),
        'year' => trim((string) ($filters['year'] ?? '')),
    ];
}

function student_filter_sql(array $filters, array &$params): string
{
    $filters = normalize_student_filters($filters);
    $where = [];

    if ($filters['q'] !== '') {
        $where[] = '(student_code LIKE :q OR full_name LIKE :q OR email LIKE :q)';
        $params['q'] = '%' . $filters['q'] . '%';
    }

    if ($filters['major'] !== '') {
        $where[] = 'major = :major';
        $params['major'] = $filters['major'];
    }

    if ($filters['year'] !== '') {
        $where[] = 'year = :year';
        $params['year'] = (int) $filters['year'];
    }

    return $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
}

function list_students(string|array $filters = '', int $limit = 0, int $offset = 0): array
{
    $pdo = get_pdo();
    $params = [];
    $filterArray = is_array($filters) ? $filters : ['q' => $filters];
    $where = student_filter_sql($filterArray, $params);
    $sql = 'SELECT * FROM students' . $where . ' ORDER BY id DESC';

    if ($limit > 0) {
        $sql .= ' LIMIT :limit OFFSET :offset';
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

function count_students(array $filters = []): int
{
    $pdo = get_pdo();
    $params = [];
    $where = student_filter_sql($filters, $params);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM students' . $where);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function list_student_majors(): array
{
    $stmt = get_pdo()->query("SELECT DISTINCT major FROM students WHERE major IS NOT NULL AND major <> '' ORDER BY major");
    return array_column($stmt->fetchAll(), 'major');
}

function list_student_years(): array
{
    $stmt = get_pdo()->query('SELECT DISTINCT year FROM students WHERE year IS NOT NULL ORDER BY year');
    return array_column($stmt->fetchAll(), 'year');
}

function count_students_by_major(): array
{
    $stmt = get_pdo()->query("SELECT COALESCE(NULLIF(major, ''), 'Chưa cập nhật') AS major, COUNT(*) AS total FROM students GROUP BY COALESCE(NULLIF(major, ''), 'Chưa cập nhật') ORDER BY total DESC");
    return $stmt->fetchAll();
}

function get_student(int $id): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $student = $stmt->fetch();

    return $student ?: null;
}

function create_student(array $data): int
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO students (student_code, full_name, email, phone, major, year)
         VALUES (:student_code, :full_name, :email, :phone, :major, :year)'
    );
    $stmt->execute([
        'student_code' => field_value('student_code', $data),
        'full_name' => field_value('full_name', $data),
        'email' => field_value('email', $data),
        'phone' => field_value('phone', $data),
        'major' => field_value('major', $data),
        'year' => field_value('year', $data) === '' ? null : (int) field_value('year', $data),
    ]);

    return (int) $pdo->lastInsertId();
}

function update_student(int $id, array $data): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'UPDATE students
         SET student_code = :student_code,
             full_name = :full_name,
             email = :email,
             phone = :phone,
             major = :major,
             year = :year
         WHERE id = :id'
    );
    $stmt->execute([
        'id' => $id,
        'student_code' => field_value('student_code', $data),
        'full_name' => field_value('full_name', $data),
        'email' => field_value('email', $data),
        'phone' => field_value('phone', $data),
        'major' => field_value('major', $data),
        'year' => field_value('year', $data) === '' ? null : (int) field_value('year', $data),
    ]);
}

function delete_student(int $id): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM students WHERE id = :id');
    $stmt->execute(['id' => $id]);
}
