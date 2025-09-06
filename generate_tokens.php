<?php
// generate_tokens.php — Admin helper to mint a batch of one-time URLs

$SECRET = 'CHANGE_ME_TO_A_LONG_RANDOM_STRING'; // <-- set this to a long random value
if (!isset($_GET['secret']) || $_GET['secret'] !== $SECRET) {
  http_response_code(401);
  exit('unauthorized');
}

$TOKENS_FILE = __DIR__ . '/tokens.txt';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'example.com';
$base = rtrim(dirname($_SERVER['PHP_SELF']), '/');
$indexUrl = $scheme . '://' . $host . $base . '/index.php';

$count = isset($_GET['n']) ? max(1, (int)$_GET['n']) : 5;

$fp = fopen($TOKENS_FILE, 'a');
if (!$fp) {
  header('Content-Type: text/plain');
  echo "ERROR: Cannot open tokens.txt for writing.";
  exit;
}
flock($fp, LOCK_EX);

header('Content-Type: text/html; charset=utf-8');
for ($i = 0; $i < $count; $i++) {
  $token = bin2hex(random_bytes(16));
  fwrite($fp, $token . PHP_EOL);
  echo htmlspecialchars($indexUrl . '?t=' . $token) . "<br>\n";
}

flock($fp, LOCK_UN);
fclose($fp);