
<?php
require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');

state_open();

$key = $_SERVER['HTTP_X_KEY'] ?? '';

$who = hash_equals(ADMIN_KEY, $key)
    ? 'admin'
    : (hash_equals(SCANNER_KEY, $key)
        ? 'scanner'
        : 'guest');

$data = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ongeldige JSON']);
    exit;
}

$err = act('scan', $data, $who);

if ($err) {
    http_response_code($err[1]);
    echo json_encode([
        'ok' => false,
        'error' => $err[0]
    ]);
    exit;
}

$result = $S['scanresult'] ?? [
    'action' => 'unknown',
    'occupancy' => 0
];

// Tijdelijk resultaat niet bewaren.
unset($S['scanresult']);

save();

echo json_encode([
    'ok' => true,
    'action' => $result['action'],
    'occupancy' => $result['occupancy']
]);
