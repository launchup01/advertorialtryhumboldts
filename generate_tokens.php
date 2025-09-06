<?php
// Token generator for one-time links
// Location of tokens file
$TOKENS_FILE = __DIR__ . '/tokens.txt';
// Determine base URL dynamically (assumes index.php is in same directory)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'example.com';
$baseUrl = $scheme . '://' . $host . '/index.php';

// Number of tokens to generate
$n = isset($_GET['n']) ? max(1, (int) $_GET['n']) : 5;

$fp = fopen($TOKENS_FILE, 'a');
if (!$fp) {
  http_response_code(500);
  echo 'Cannot open tokens file.';
  exit;
}
flock($fp, LOCK_EX);

for ($i = 0; $i < $n; $i++) {
  $token = bin2hex(random_bytes(16));
  fwrite($fp, $token . PHP_EOL);
  echo $baseUrl . '?t=' . $token . "<br>\n";
}

flock($fp, LOCK_UN);
fclose($fp);
?>