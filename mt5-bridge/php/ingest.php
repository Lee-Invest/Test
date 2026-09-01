<?php
// Ontvangt een enkele trade van de MT5 EA (POST, JSON body).
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

if (BRIDGE_SECRET !== '') {
    $given = $_SERVER['HTTP_X_BRIDGE_SECRET'] ?? '';
    if ($given !== BRIDGE_SECRET) {
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload || empty($payload['account'])) {
    http_response_code(400);
    echo json_encode(['error' => "invalid payload, 'account' verplicht"]);
    exit;
}

$account = (string) $payload['account'];
$trade = [
    'ticket'    => $payload['ticket'] ?? null,
    'datum'     => $payload['datum'] ?? '',
    'asset'     => $payload['asset'] ?? '',
    'resultaat' => $payload['resultaat'] ?? 0,
    'rr'        => $payload['rr'] ?? '3',
    'notities'  => $payload['notities'] ?? '',
];

$data = load_trades();
if (!isset($data[$account])) $data[$account] = [];

// Voorkom duplicaten als de EA hetzelfde ticket twee keer stuurt.
if ($trade['ticket'] !== null) {
    $data[$account] = array_values(array_filter($data[$account], function ($t) use ($trade) {
        return ($t['ticket'] ?? null) !== $trade['ticket'];
    }));
}

$data[$account][] = $trade;
save_trades($data);

echo json_encode(['ok' => true, 'count' => count($data[$account])]);
