<?php
// Accounts allowed to sync. Add more usernames if you ever need them.
// Password hashes are generated with password_hash() — never store plain
// passwords. To generate one: php -r "echo password_hash('Test', PASSWORD_DEFAULT);"
$ACCOUNTS = [
    'Test' => '$2y$12$7xqwnytJPwsnkJhQGdO2kuRqw0ZoG2iEdsGyiedrM.fF1u/f.tUji', // password: Test
];

define('DATA_DIR', __DIR__ . '/data');

function check_credentials($username, $password) {
    global $ACCOUNTS;
    if (!isset($ACCOUNTS[$username])) return false;
    return password_verify($password, $ACCOUNTS[$username]);
}

function state_file($username) {
    // Keep filenames safe regardless of username content.
    $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
    return DATA_DIR . '/' . $safe . '.json';
}

function send_cors_headers() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
