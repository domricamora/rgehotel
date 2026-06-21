<?php
// Minimal "notify me" capture for the Coming Soon page.
// Appends valid emails to subscribers.csv (one row per signup). No DB needed.
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'invalid_email']);
    exit;
}

$file = __DIR__ . '/subscribers.csv';
$row  = [date('c'), $email, $_SERVER['REMOTE_ADDR'] ?? ''];

$fh = @fopen($file, 'ab');
if ($fh === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'storage_unavailable']);
    exit;
}
if (flock($fh, LOCK_EX)) {
    fputcsv($fh, $row);
    flock($fh, LOCK_UN);
}
fclose($fh);

echo json_encode(['ok' => true]);
