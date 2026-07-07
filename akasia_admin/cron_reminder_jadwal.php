<?php

date_default_timezone_set('Asia/Jakarta');

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

$allowedToken = 'akasia-reminder-2026';
$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $token = $_GET['token'] ?? '';
    if ($token !== $allowedToken) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$targetDate = trim((string) ($_GET['date'] ?? ''));
if ($targetDate === '' && $isCli) {
    $targetDate = null;
}

if ($targetDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)) {
    http_response_code(400);
    exit('Format date tidak valid. Gunakan YYYY-MM-DD.');
}

try {
    $result = processReminderJadwalServis($pdo, $targetDate ?: null);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'reason' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
