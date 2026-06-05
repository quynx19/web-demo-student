<?php

declare(strict_types=1);

// Nhật ký ứng dụng: ghi thao tác người dùng, lỗi và exception vào logs/app.log.
function app_log_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log';
}

function clean_log_value(mixed $value): mixed
{
    if (is_array($value)) {
        $clean = [];
        foreach ($value as $key => $item) {
            $keyName = strtolower((string) $key);
            if (in_array($keyName, ['password', 'current_password', 'new_password', 'confirm_password', 'password_hash', 'csrf_token'], true)) {
                $clean[$key] = '[hidden]';
                continue;
            }
            $clean[$key] = clean_log_value($item);
        }

        return $clean;
    }

    if (is_scalar($value) || $value === null) {
        return str_replace(["\r", "\n"], ' ', (string) $value);
    }

    return get_debug_type($value);
}

function write_log(string $level, string $event, string $message, array $context = []): void
{
    $logFile = app_log_path();
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $level = strtoupper($level);
    $event = strtoupper($event);
    $contextJson = json_encode(clean_log_value($context), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($contextJson === false) {
        $contextJson = '{}';
    }

    $line = sprintf(
        '[%s] %s %s user_id=%s username=%s role=%s ip=%s method=%s uri=%s message="%s" context=%s%s',
        date('Y-m-d H:i:s'),
        clean_log_value($level),
        clean_log_value($event),
        clean_log_value($_SESSION['user_id'] ?? 'guest'),
        clean_log_value($_SESSION['username'] ?? 'guest'),
        clean_log_value($_SESSION['role'] ?? 'guest'),
        clean_log_value($_SERVER['REMOTE_ADDR'] ?? 'cli'),
        clean_log_value($_SERVER['REQUEST_METHOD'] ?? 'CLI'),
        clean_log_value($_SERVER['REQUEST_URI'] ?? ''),
        clean_log_value($message),
        $contextJson,
        PHP_EOL
    );

    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

function install_app_log_handlers(): void
{
    static $installed = false;
    if ($installed) {
        return;
    }
    $installed = true;

    set_exception_handler(function (Throwable $exception): void {
        write_log('ERROR', 'UNCAUGHT_EXCEPTION', 'Unhandled exception', [
            'type' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        http_response_code(500);
        if (PHP_SAPI !== 'cli') {
            echo 'Đã xảy ra lỗi hệ thống. Vui lòng kiểm tra logs/app.log.';
        }
    });

    register_shutdown_function(function (): void {
        $error = error_get_last();
        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        write_log('ERROR', 'FATAL_ERROR', 'Fatal PHP error', [
            'type' => $error['type'],
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
        ]);
    });
}
