<?php
// Loads the full Trade Journal state for one account (GET ?username=&password=).
require __DIR__ . '/config.php';

send_cors_headers();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';

if (!check_credentials($username, $password)) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$file = state_file($username);
if (!file_exists($file)) {
    // No saved state yet for this account — not an error, just empty.
    echo json_encode(['found' => false, 'state' => null]);
    exit;
}

$raw = file_get_contents($file);
$state = json_decode($raw, true);

echo json_encode(['found' => true, 'state' => $state]);
