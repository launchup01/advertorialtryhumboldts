<?php
// generate_tokens.php — optional batch token generator for manual tests (NOT used by ad flow)
// LOCKED behind a secret so random visitors can't mint tokens.
//
// Usage: /generate_tokens.php?n=5&secret=YOUR_SECRET
// Replace the $SECRET below with a strong random string.
$SECRET = 'CHANGE_ME_TO_A_LONG_RANDOM_STRING';  // <-- EDIT THIS
if (!isset($_GET['secret']) || $_GET['secret'] !== $SECRET) {
  http_response_code(401);
  exit('unauthorized');
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

$TOKENS_FILE = __DIR__ . '/tokens.txt';
$BASE_URL    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
             . '://' . $_SERVER['HTTP_HOST'] . '/index.php';

$count = max(1, min(1000, (int)($_GET['n'] ?? 5)));
$ttl   = max(60, (int)($_GET['ttl'] ?? 900)); // default 15 min
$iat   = time();
$exp   = $iat + $ttl;

$fp = @fopen($TOKENS_FILE, 'a');
if (!$fp) {
  $TOKENS_FILE = sys_get_temp_dir() . '/tokens.txt';
  $fp = @fopen($TOKENS_FILE, 'a');
  if (!$fp) {
    http_response_code(500);
    echo "Token store not writable.";
    exit;
  }
}
flock($fp, LOCK_EX);

$links = [];
for ($i = 0; $i < $count; $i++) {
  $token = bin2hex(random_bytes(16));
  fwrite($fp, $token . '|GENERATOR|' . $exp . PHP_EOL);
  $links[] = htmlspecialchars($BASE_URL . '?t=' . urlencode($token), ENT_QUOTES, 'UTF-8');
}

flock($fp, LOCK_UN);
fclose($fp);

echo "<!doctype html><meta charset='utf-8'><title>Minted links</title><pre>";
echo implode("\n", $links);
echo "</pre>";
