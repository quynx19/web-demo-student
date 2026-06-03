<?php

declare(strict_types=1);

// Quản lý điểm: danh sách 3 môn cố định và thao tác lưu/xem điểm sinh viên.
require_once __DIR__ . '/../config/database.php';

function grade_subjects(): array
{
    return [
        'MATH' => 'Toán',
        'PROGRAMMING' => 'Lập trình',
        'DATABASE' => 'Cơ sở dữ liệu',
    ];
}

function list_student_grades(int $studentId): array
{
    $stmt = get_pdo()->prepare(
        'SELECT id, student_id, subject_code, subject_name, score, created_at, updated_at
         FROM student_grades
         WHERE student_id = :student_id
         ORDER BY FIELD(subject_code, "MATH", "PROGRAMMING", "DATABASE")'
    );
    $stmt->execute(['student_id' => $studentId]);

    return $stmt->fetchAll();
}

function initialize_student_grades(int $studentId): void
{
    $stmt = get_pdo()->prepare(
        'INSERT IGNORE INTO student_grades (student_id, subject_code, subject_name, score)
         VALUES (:student_id, :subject_code, :subject_name, 0)'
    );

    foreach (grade_subjects() as $subjectCode => $subjectName) {
        $stmt->execute([
            'student_id' => $studentId,
            'subject_code' => $subjectCode,
            'subject_name' => $subjectName,
        ]);
    }
}

function normalize_student_grades(array $data): array
{
    $submittedGrades = $data['grades'] ?? null;
    if (!is_array($submittedGrades) || !array_is_list($submittedGrades)) {
        return [];
    }

    $grades = [];
    foreach ($submittedGrades as $grade) {
        if (!is_array($grade)) {
            return [];
        }

        $subjectCode = trim((string) ($grade['subject_code'] ?? ''));
        $score = $grade['score'] ?? null;
        if ($subjectCode === '' || !is_scalar($score) || !is_numeric((string) $score)) {
            return [];
        }

        $grades[$subjectCode] = (float) $score;
    }

    return $grades;
}

function validate_student_grades(array $data): array
{
    $subjects = grade_subjects();
    $grades = normalize_student_grades($data);

    if (
        count($grades) !== count($subjects)
        || array_diff(array_keys($grades), array_keys($subjects)) !== []
        || array_diff(array_keys($subjects), array_keys($grades)) !== []
    ) {
        return ['grades' => 'Phải gửi đủ điểm của đúng 3 môn học.'];
    }

    foreach ($grades as $subjectCode => $score) {
        if ($score < 0 || $score > 10) {
            return ['grades' => sprintf('Điểm môn %s phải từ 0 đến 10.', $subjects[$subjectCode])];
        }
    }

    return [];
}

function replace_student_grades(int $studentId, array $data): void
{
    $subjects = grade_subjects();
    $grades = normalize_student_grades($data);
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO student_grades (student_id, subject_code, subject_name, score)
         VALUES (:student_id, :subject_code, :subject_name, :score)
         ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name), score = VALUES(score)'
    );

    $pdo->beginTransaction();
    try {
        foreach ($subjects as $subjectCode => $subjectName) {
            $stmt->execute([
                'student_id' => $studentId,
                'subject_code' => $subjectCode,
                'subject_name' => $subjectName,
                'score' => $grades[$subjectCode],
            ]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}
