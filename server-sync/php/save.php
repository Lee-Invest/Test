<?php
// Saves the full Trade Journal state for one account (POST, JSON body).
require __DIR__ . '/config.php';

send_cors_headers();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload || !isset($payload['username'], $payload['password'], $payload['state'])) {
    http_response_code(400);
    echo json_encode(['error' => "invalid payload, 'username', 'password' en 'state' verplicht"]);
    exit;
}

if (!check_credentials($payload['username'], $payload['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0775, true);
}

$file = state_file($payload['username']);
$json = json_encode($payload['state'], JSON_UNESCAPED_UNICODE);
if ($json === false) {
    http_response_code(400);
    echo json_encode(['error' => "'state' is geen geldige JSON"]);
    exit;
}

file_put_contents($file, $json, LOCK_EX);

echo json_encode(['ok' => true, 'savedAt' => date('c')]);
