<?php
// Wordt aangeroepen door de Trade Journal pagina om trades op te halen (GET ?account=...).
require __DIR__ . '/config.php';

send_cors_headers();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$account = $_GET['account'] ?? '';
$data = load_trades();
$accountTrades = $data[$account] ?? [];

$result = array_map(function ($t) {
    return [
        'datum'     => $t['datum'] ?? '',
        'asset'     => $t['asset'] ?? '',
        'resultaat' => $t['resultaat'] ?? 0,
        'rr'        => $t['rr'] ?? '3',
        'notities'  => $t['notities'] ?? '',
    ];
}, $accountTrades);

echo json_encode(array_values($result));
