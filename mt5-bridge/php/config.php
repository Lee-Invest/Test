<?php
// Pas dit eventueel aan. Laat leeg om geen secret-check te doen.
define('BRIDGE_SECRET', '');

// Waar trades worden opgeslagen. Moet beschrijfbaar zijn door PHP (chmod 755/775 map).
define('DATA_FILE', __DIR__ . '/trades.json');

function load_trades() {
    if (!file_exists(DATA_FILE)) return [];
    $raw = file_get_contents(DATA_FILE);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function save_trades($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function send_cors_headers() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Bridge-Secret');
}
