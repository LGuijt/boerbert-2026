<?php
// Alleen voor de pasjeslezers: POST api.php met header X-Key en JSON {"f":"douche","dir":"in|out","card":"123"}
require __DIR__.'/lib.php';
header('Content-Type: application/json');
state_open();
$key = $_SERVER['HTTP_X_KEY'] ?? '';
$who = hash_equals(ADMIN_KEY,$key) ? 'admin' : (hash_equals(SCANNER_KEY,$key) ? 'scanner' : 'guest');
$err = act('scan', json_decode(file_get_contents('php://input'),true) ?: [], $who);
if($err){ http_response_code($err[1]); exit(json_encode(['error'=>$err[0]])); }
save();
echo json_encode(['ok'=>true]);
 