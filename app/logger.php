<?php

declare(strict_types=1);

function write_log(string $level, string $event, string $message, array $context = []): void
{
    $logFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $username = $_SESSION['username'] ?? 'guest';
    $role = $_SESSION['role'] ?? 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($contextJson === false) {
        $contextJson = '{}';
    }

    $line = sprintf(
        '[%s] %s %s username=%s role=%s ip=%s method=%s uri=%s message="%s" context=%s%s',
        date('Y-m-d H:i:s'),
        strtoupper($level),
        strtoupper($event),
        str_replace(["\r", "\n"], ' ', (string) $username),
        str_replace(["\r", "\n"], ' ', (string) $role),
        str_replace(["\r", "\n"], ' ', (string) $ip),
        str_replace(["\r", "\n"], ' ', (string) $method),
        str_replace(["\r", "\n"], ' ', (string) $uri),
        str_replace(["\r", "\n"], ' ', $message),
        $contextJson,
        PHP_EOL
    );

    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
